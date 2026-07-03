<?php

use Illuminate\Support\Facades\Http;
use Mjoc1985\Fhr\Data\FhrBookingData;
use Mjoc1985\Fhr\Data\FhrOrderResponseData;
use Mjoc1985\Fhr\Enums\FhrProductType;
use Mjoc1985\Fhr\Services\FhrPartnerService;

it('fetches partner order details by guid', function () {
    Http::fake([
        '*/partner/order/details/order-guid' => Http::response([
            'status' => 'success',
            'total' => 89.99,
        ], 200),
    ]);

    $details = FhrPartnerService::make()->getOrderDetails('order-guid');

    expect($details['status'])->toBe('success');

    Http::assertSent(fn ($request) => str_contains($request->url(), 'partner/order/details/order-guid')
        && $request->method() === 'GET');
});

it('lists bookings by email', function () {
    Http::fake([
        '*/partner/bookings' => Http::response([
            [
                'bookingId' => 'BK1',
                'dateCreated' => '2026-01-01T10:00:00+00:00',
                'status' => 'Active',
                'bookingSource' => 'FHR',
                'productType' => 'Parking',
                'surname' => 'Smith',
                'email' => 'john@example.com',
                'arrivalDate' => '2026-02-01T10:00:00+00:00',
                'returnDate' => '2026-02-08T10:00:00+00:00',
                'product' => 'Purple Parking',
                'description' => '8 days',
                'airportCode' => 'LHR',
                'paymentRef' => 'pi_123',
                'registration' => 'AB12CDE',
                'pricePaid' => 89.99,
            ],
        ], 200),
    ]);

    $bookings = FhrPartnerService::make()->getBookingsByEmail('john@example.com', FhrProductType::Parking);

    expect($bookings)->toHaveCount(1)
        ->and($bookings->first())->toBeInstanceOf(FhrBookingData::class)
        ->and($bookings->first()->bookingId)->toBe('BK1');

    Http::assertSent(fn ($request) => str_ends_with($request->url(), '/partner/bookings')
        && $request['email'] === 'john@example.com'
        && $request['type'] === 'Parking');
});

it('unwraps a wrapped bookings response', function () {
    Http::fake([
        '*/partner/bookings' => Http::response([
            'bookings' => [
                [
                    'bookingId' => 'BK1',
                    'dateCreated' => '2026-01-01T10:00:00+00:00',
                    'status' => 'Active',
                    'bookingSource' => 'FHR',
                    'productType' => 'Parking',
                    'surname' => 'Smith',
                    'email' => 'john@example.com',
                    'arrivalDate' => '2026-02-01T10:00:00+00:00',
                    'returnDate' => '2026-02-08T10:00:00+00:00',
                    'product' => 'Purple Parking',
                    'description' => '8 days',
                    'airportCode' => 'LHR',
                    'paymentRef' => 'pi_123',
                    'registration' => 'AB12CDE',
                    'pricePaid' => 89.99,
                ],
            ],
            'count' => 1,
        ], 200),
    ]);

    $bookings = FhrPartnerService::make()->getBookingsByEmail('john@example.com');

    expect($bookings)->toHaveCount(1)
        ->and($bookings->first())->toBeInstanceOf(FhrBookingData::class)
        ->and($bookings->first()->bookingId)->toBe('BK1');
});

it('returns no bookings for an empty object response', function () {
    Http::fake([
        '*/partner/bookings' => Http::response(['count' => 0], 200),
    ]);

    $bookings = FhrPartnerService::make()->getBookingsByEmail('john@example.com');

    expect($bookings)->toBeEmpty();
});

it('gets full booking details', function () {
    Http::fake([
        '*/partner/booking/details' => Http::response(['bookingId' => 'BK1', 'status' => 'Active'], 200),
    ]);

    $details = FhrPartnerService::make()->getBookingDetails('BK1', FhrProductType::Parking, 'john@example.com');

    expect($details['bookingId'])->toBe('BK1');

    Http::assertSent(fn ($request) => str_ends_with($request->url(), '/partner/booking/details')
        && $request['email'] === 'john@example.com');
});

it('checks cancellation eligibility', function () {
    Http::fake([
        '*/partner/booking/can-cancel' => Http::response(['canCancel' => true], 200),
    ]);

    $result = FhrPartnerService::make()->canCancel('BK1', 'john@example.com');

    expect($result['canCancel'])->toBeTrue();
});

it('cancels a booking and normalises the response', function () {
    Http::fake([
        '*/partner/booking/cancel' => Http::response(['status' => 'Cancelled', 'refundAmount' => 50.0], 200),
    ]);

    $result = FhrPartnerService::make()->cancelBooking('BK1', 'john@example.com');

    expect($result)->toBeInstanceOf(FhrOrderResponseData::class)
        ->and($result->success)->toBeTrue()
        ->and($result->refundAmount)->toBe(50.0);

    Http::assertSent(fn ($request) => str_ends_with($request->url(), '/partner/booking/cancel')
        && $request->method() === 'DELETE');
});

it('treats an already-cancelled booking as success', function () {
    Http::fake([
        '*/partner/booking/cancel' => Http::response([
            'status' => 'error',
            'brokenRules' => [['rule' => 'Booking already cancelled']],
        ], 200),
    ]);

    $result = FhrPartnerService::make()->cancelBooking('BK1', 'john@example.com');

    expect($result->success)->toBeTrue();
});

it('gets booking financial data', function () {
    Http::fake([
        '*/partner/booking/financial' => Http::response(['bookingSourceCommission' => 12.5], 200),
    ]);

    $financial = FhrPartnerService::make()->getBookingFinancial('SPS0', 'BK1', 'john@example.com');

    expect($financial['bookingSourceCommission'])->toBe(12.5);

    Http::assertSent(fn ($request) => $request['source'] === 'SPS0' && $request['bookingId'] === 'BK1');
});

it('confirms a booking with a payment reference', function () {
    Http::fake([
        '*/partner/booking/confirm' => Http::response(['success' => true], 200),
    ]);

    $result = FhrPartnerService::make()->confirmWithPaymentRef('order-guid', 'pi_123');

    expect($result['success'])->toBeTrue();

    Http::assertSent(fn ($request) => $request['orderId'] === 'order-guid'
        && $request['paymentRef'] === 'pi_123');
});

it('gets product details', function () {
    Http::fake([
        '*/partner/product/123' => Http::response(['id' => 123, 'productName' => 'Purple Parking'], 200),
    ]);

    $product = FhrPartnerService::make()->getProduct(123);

    expect($product['id'])->toBe(123)
        ->and($product['productName'])->toBe('Purple Parking');

    Http::assertSent(fn ($request) => str_contains($request->url(), 'partner/product/123')
        && $request->method() === 'GET');
});
