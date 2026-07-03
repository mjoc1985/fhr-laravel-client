<?php

use Illuminate\Support\Facades\Http;
use Mjoc1985\Fhr\Data\FhrCustomerData;
use Mjoc1985\Fhr\Data\FhrOrderResponseData;
use Mjoc1985\Fhr\Data\FhrSearchResultData;
use Mjoc1985\Fhr\Data\FhrSearchValidityData;
use Mjoc1985\Fhr\Enums\FhrProductType;
use Mjoc1985\Fhr\Services\FhrBookingService;
use Mjoc1985\Fhr\Services\FhrCartService;
use Mjoc1985\Fhr\Services\FhrOrderService;
use Mjoc1985\Fhr\Services\FhrSearchService;

function customer(): FhrCustomerData
{
    return new FhrCustomerData(
        title: 'Mr',
        firstName: 'John',
        lastName: 'Smith',
        email: 'john@example.com',
        phone: '07700900000',
    );
}

// ---------------------------------------------------------------------------
// Search: get by ID & validity check
// ---------------------------------------------------------------------------

it('retrieves cached search results by id', function () {
    Http::fake([
        '*/search/abc123' => Http::response(['searchId' => 'abc123', 'results' => []], 200),
    ]);

    $result = FhrSearchService::make()->getSearchById('abc123');

    expect($result)->toBeInstanceOf(FhrSearchResultData::class)
        ->and($result->searchId)->toBe('abc123');

    Http::assertSent(fn ($request) => str_contains($request->url(), 'search/abc123')
        && $request->method() === 'GET');
});

it('reports an active search as valid', function () {
    Http::fake([
        '*/search-check/abc123' => Http::response([
            'searchId' => 'abc123',
            'valid' => true,
            'status' => 'active',
        ], 200),
    ]);

    $validity = FhrSearchService::make()->checkSearch('abc123');

    expect($validity)->toBeInstanceOf(FhrSearchValidityData::class)
        ->and($validity->isValid())->toBeTrue()
        ->and($validity->status)->toBe('active');
});

it('normalises an expired (410) search into an invalid result', function () {
    Http::fake([
        '*/search-check/expired1' => Http::response([
            'searchId' => 'expired1',
            'valid' => false,
            'status' => 'expired',
        ], 410),
    ]);

    $validity = FhrSearchService::make()->checkSearch('expired1');

    expect($validity->isValid())->toBeFalse()
        ->and($validity->status)->toBe('expired');
});

// ---------------------------------------------------------------------------
// Cart: get items & validate order
// ---------------------------------------------------------------------------

it('lists cart items', function () {
    Http::fake([
        '*/cart/cart-123' => Http::response([
            'items' => [
                [
                    '_id' => 'item-1',
                    'searchId' => 'abc123',
                    'name' => 'Purple Parking',
                    'type' => 'Parking',
                    'product' => '1',
                    'option' => '2',
                    'quantity' => 1,
                    'price' => ['amount' => 49.99, 'currency' => 'GBP'],
                ],
            ],
        ], 200),
    ]);

    $items = FhrCartService::make()->getCartItems('cart-123');

    expect($items)->toHaveCount(1)
        ->and($items->first()->itemId)->toBe('item-1')
        ->and($items->first()->name)->toBe('Purple Parking');
});

it('returns no cart items for an associative object without an items key', function () {
    Http::fake([
        '*/cart/cart-123' => Http::response(['cart' => ['id' => 'cart-123'], 'total' => 49.99], 200),
    ]);

    $items = FhrCartService::make()->getCartItems('cart-123');

    expect($items)->toBeEmpty();
});

it('pre-validates an order without taking payment', function () {
    Http::fake([
        '*/cart/cart-123/order/validate' => Http::response([
            'success' => true,
            'order' => 'order-guid',
        ], 200),
    ]);

    $result = FhrCartService::make()->validateOrder('cart-123', customer(), [
        ['type' => FhrProductType::Parking, 'vehicle' => ['vehicle_reg' => 'AB12 CDE']],
    ], 'item-1');

    expect($result)->toBeInstanceOf(FhrOrderResponseData::class)
        ->and($result->success)->toBeTrue()
        ->and($result->order)->toBe('order-guid');

    Http::assertSent(fn ($request) => str_contains($request->url(), 'cart/cart-123/order/validate')
        && $request->method() === 'POST');
});

// ---------------------------------------------------------------------------
// Synchronous booking
// ---------------------------------------------------------------------------

it('creates and confirms a synchronous booking', function () {
    Http::fake([
        '*/booking' => Http::response(['success' => true, 'order' => 'order-guid'], 200),
    ]);

    $result = FhrBookingService::make()->book(
        searchId: 'abc123',
        type: FhrProductType::Parking,
        productId: '123',
        optionId: '456',
        customer: customer(),
        flights: [['flightNumber' => 'BA123', 'direction' => 'outbound', 'terminal' => '5']],
        vehicle: ['reg' => 'AB12 CDE', 'make' => 'Ford'],
    );

    expect($result->success)->toBeTrue()
        ->and($result->order)->toBe('order-guid');

    Http::assertSent(fn ($request) => str_ends_with($request->url(), '/booking')
        && $request['booking']['searchId'] === 'abc123'
        && $request['params']['flights'][0]['direction'] === 'outbound');
});

it('creates a booking with a detailed response', function () {
    Http::fake([
        '*/booking-create' => Http::response([
            'success' => true,
            'status' => 'success',
            'total' => 89.99,
            'order' => ['id' => 123, 'total' => 89.99, 'currency' => 'GBP'],
            'items' => [],
        ], 200),
    ]);

    $result = FhrBookingService::make()->bookWithDetails(
        searchId: 'abc123',
        type: FhrProductType::Parking,
        productId: '123',
        optionId: '456',
        customer: customer(),
    );

    expect($result)->toBeArray()
        ->and($result['status'])->toBe('success')
        ->and($result['order']['total'])->toBe(89.99);
});

// ---------------------------------------------------------------------------
// Order details
// ---------------------------------------------------------------------------

it('fetches order details by guid', function () {
    Http::fake([
        '*/order/details/order-guid' => Http::response([
            'status' => 'success',
            'total' => 89.99,
            'order' => ['id' => 123, 'total' => 89.99, 'currency' => 'GBP'],
        ], 200),
    ]);

    $details = FhrOrderService::make()->getOrderDetails('order-guid');

    expect($details)->toBeArray()
        ->and($details['status'])->toBe('success')
        ->and($details['order']['id'])->toBe(123);

    Http::assertSent(fn ($request) => str_contains($request->url(), 'order/details/order-guid')
        && $request->method() === 'GET');
});
