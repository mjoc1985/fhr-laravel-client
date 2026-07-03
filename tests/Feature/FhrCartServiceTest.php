<?php

use Illuminate\Support\Facades\Http;
use Mjoc1985\Fhr\Data\FhrCartData;
use Mjoc1985\Fhr\Data\FhrCartTotalsData;
use Mjoc1985\Fhr\Data\FhrCustomerData;
use Mjoc1985\Fhr\Data\FhrFlightData;
use Mjoc1985\Fhr\Data\FhrOrderResponseData;
use Mjoc1985\Fhr\Data\FhrPassengerData;
use Mjoc1985\Fhr\Data\FhrVehicleData;
use Mjoc1985\Fhr\Enums\FhrProductType;
use Mjoc1985\Fhr\Services\FhrCartService;

beforeEach(function () {
    config([
        'fhr.base_url' => 'https://www.bookfhr.com/api',
        'fhr.token' => 'test-token',
        'fhr.source' => 'TEST',
    ]);
});

it('redacts customer PII and the vehicle registration before logging an order payload', function () {
    $service = FhrCartService::make();

    $method = (new ReflectionClass($service))->getMethod('redactPayload');
    $method->setAccessible(true);

    $redacted = $method->invoke($service, [
        'customer' => ['firstName' => 'John', 'email' => 'john@example.com'],
        'products' => [
            [
                '_id' => 'item-1',
                'type' => 'Parking',
                'vehicle' => ['reg' => 'AB12 CDE', 'make' => 'Ford', 'model' => 'Focus'],
                'additional_passengers' => [['name' => 'Jane']],
            ],
        ],
    ]);

    expect($redacted['customer'])->toBe(['redacted' => true])
        ->and($redacted['products'][0]['vehicle']['reg'])->toBe('redacted')
        ->and($redacted['products'][0]['additional_passengers'])->toBe(['redacted' => true])
        // Non-personal vehicle fields stay intact for debugging.
        ->and($redacted['products'][0]['vehicle']['make'])->toBe('Ford')
        ->and($redacted['products'][0]['vehicle']['model'])->toBe('Focus')
        ->and($redacted['products'][0]['_id'])->toBe('item-1');
});

it('can create a cart', function () {
    Http::fake([
        'https://www.bookfhr.com/api/cart/create' => Http::response([
            'cartId' => 'cart-123',
            'currency' => 'GBP',
        ], 200),
    ]);

    $service = FhrCartService::make();
    $cart = $service->createCart('GBP');

    expect($cart)->toBeInstanceOf(FhrCartData::class)
        ->and($cart->cartId)->toBe('cart-123')
        ->and($cart->currency)->toBe('GBP');

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'cart/create')
            && $request['currency'] === 'GBP';
    });
});

it('can add item to cart', function () {
    Http::fake([
        'https://www.bookfhr.com/api/cart/cart-123' => Http::response([
            'cartId' => 'cart-123',
            'currency' => 'GBP',
            'totals' => [
                'total' => 54.99,
                'currency' => 'GBP',
                'discount' => null,
                'baseCurrencyTotal' => 54.99,
                'baseCurrency' => 'GBP',
            ],
        ], 200),
    ]);

    $service = FhrCartService::make();
    $cart = $service->addItem(
        cartId: 'cart-123',
        searchId: 'search-456',
        productId: 'product-789',
        optionId: 'option-101',
        productType: FhrProductType::Parking,
        quantity: 1,
    );

    expect($cart)->toBeInstanceOf(FhrCartData::class)
        ->and($cart->cartId)->toBe('cart-123')
        ->and($cart->totals)->toBeInstanceOf(FhrCartTotalsData::class)
        ->and($cart->totals->total)->toBe(54.99);

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'cart/cart-123')
            && $request['type'] === 'Parking'
            && $request['product'] === 'product-789'
            && $request['option'] === 'option-101'
            && $request['qty'] === 1;
    });
});

it('can remove item from cart', function () {
    Http::fake([
        'https://www.bookfhr.com/api/cart/cart-123/remove' => Http::response([
            'cartId' => 'cart-123',
            'currency' => 'GBP',
        ], 200),
    ]);

    $service = FhrCartService::make();
    $cart = $service->removeItem('cart-123', 'item-456');

    expect($cart)->toBeInstanceOf(FhrCartData::class)
        ->and($cart->cartId)->toBe('cart-123');

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'cart/cart-123/remove')
            && $request['itemId'] === 'item-456';
    });
});

it('can get cart totals', function () {
    Http::fake([
        'https://www.bookfhr.com/api/cart/cart-123/totals' => Http::response([
            'totals' => [
                'total' => 109.98,
                'currency' => 'GBP',
                'baseTotal' => 109.98,
                'baseCurrency' => 'GBP',
                'exchangeRate' => 1.0,
                'discountAmount' => 10.00,
                'discountSaving' => 10.00,
                'payNow' => 109.98,
                'payLater' => 0.00,
                'grandTotal' => 109.98,
            ],
        ], 200),
    ]);

    $service = FhrCartService::make();
    $totals = $service->getTotals('cart-123');

    expect($totals)->toBeInstanceOf(FhrCartTotalsData::class)
        ->and($totals->total)->toBe(109.98)
        ->and($totals->discountAmount)->toBe(10.00)
        ->and($totals->getTotalInPence())->toBe(10998);
});

it('can submit order', function () {
    Http::fake([
        'https://www.bookfhr.com/api/cart/cart-123/order/submit' => Http::response([
            'success' => true,
            'order' => 'order-guid-123',
            'message' => 'Order created successfully',
        ], 200),
    ]);

    $service = FhrCartService::make();
    $customer = FhrCustomerData::from([
        'title' => 'Mr',
        'firstName' => 'John',
        'lastName' => 'Smith',
        'email' => 'john@example.com',
        'phone' => '+447123456789',
    ]);

    $result = $service->submitOrder('cart-123', $customer, [
        [
            'type' => FhrProductType::Parking,
            'vehicle' => ['vehicle_reg' => 'AB12 CDE'],
            'flight' => null,
        ],
    ]);

    expect($result)->toBeInstanceOf(FhrOrderResponseData::class)
        ->and($result->success)->toBeTrue()
        ->and($result->order)->toBe('order-guid-123');

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'order/submit')
            && $request['customer']['email'] === 'john@example.com'
            && $request['products'][0]['type'] === 'Parking';
    });
});

it('can confirm an order after external payment', function () {
    Http::fake([
        'https://www.bookfhr.com/api/cart/cart-confirm-1/order/confirm' => Http::response([
            'success' => true,
            'order' => 'cart-confirm-1',
            'message' => 'Order confirmed',
        ], 200),
    ]);

    $service = FhrCartService::make();
    $result = $service->confirmOrder('cart-confirm-1');

    expect($result)->toBeInstanceOf(FhrOrderResponseData::class)
        ->and($result->success)->toBeTrue()
        ->and($result->order)->toBe('cart-confirm-1');

    Http::assertSent(fn ($request) => str_contains($request->url(), 'cart/cart-confirm-1/order/confirm') && $request->method() === 'POST');
});

it('returns the cartId as order when confirm response omits it', function () {
    Http::fake([
        'https://www.bookfhr.com/api/cart/cart-confirm-2/order/confirm' => Http::response([
            'success' => true,
        ], 200),
    ]);

    $service = FhrCartService::make();
    $result = $service->confirmOrder('cart-confirm-2');

    expect($result->order)->toBe('cart-confirm-2');
});

it('can submit parking order', function () {
    Http::fake([
        'https://www.bookfhr.com/api/cart/cart-123/order/submit' => Http::response([
            'success' => true,
            'order' => 'parking-order-123',
            'message' => 'Parking order created',
        ], 200),
    ]);

    $service = FhrCartService::make();
    $customer = FhrCustomerData::from([
        'title' => 'Mrs',
        'firstName' => 'Jane',
        'lastName' => 'Doe',
        'email' => 'jane@example.com',
        'phone' => '+447987654321',
    ]);
    $vehicle = FhrVehicleData::from([
        'vehicleReg' => 'XY99 ZZZ',
        'vehicleMake' => 'Ford',
        'vehicleModel' => 'Focus',
        'colour' => 'Blue',
        'passengers' => 2,
    ]);
    $flight = FhrFlightData::from([
        'outboundFlight' => 'EZY123',
        'inboundFlight' => 'EZY456',
        'outboundTerminal' => 'T1',
        'inboundTerminal' => 'T1',
    ]);

    $result = $service->submitParkingOrder('cart-123', $customer, $vehicle, $flight);

    expect($result->success)->toBeTrue()
        ->and($result->order)->toBe('parking-order-123');

    Http::assertSent(function ($request) {
        return $request['products'][0]['type'] === 'Parking'
            && isset($request['products'][0]['vehicle'])
            && isset($request['products'][0]['flight']);
    });
});

it('can submit lounge order', function () {
    Http::fake([
        'https://www.bookfhr.com/api/cart/cart-123/order/submit' => Http::response([
            'success' => true,
            'order' => 'lounge-order-456',
            'message' => 'Lounge order created',
        ], 200),
    ]);

    $service = FhrCartService::make();
    $customer = FhrCustomerData::from([
        'title' => 'Mr',
        'firstName' => 'Bob',
        'lastName' => 'Jones',
        'email' => 'bob@example.com',
        'phone' => '+447111222333',
    ]);

    $result = $service->submitLoungeOrder('cart-123', $customer);

    expect($result->success)->toBeTrue()
        ->and($result->order)->toBe('lounge-order-456');

    Http::assertSent(function ($request) {
        return $request['products'][0]['type'] === 'Lounge';
    });
});

it('can submit lounge order with additional passengers', function () {
    Http::fake([
        'https://www.bookfhr.com/api/cart/cart-123/order/submit' => Http::response([
            'success' => true,
            'order' => 'lounge-order-789',
            'message' => 'Lounge order with passengers created',
        ], 200),
    ]);

    $service = FhrCartService::make();
    $customer = FhrCustomerData::from([
        'title' => 'Mr',
        'firstName' => 'John',
        'lastName' => 'Smith',
        'email' => 'john@example.com',
        'phone' => '+447123456789',
    ]);

    $additionalPassengers = [
        new FhrPassengerData(
            typeNo: 1,
            type: 'Adult',
            title: 'Mrs',
            firstName: 'Jane',
            lastName: 'Smith',
        ),
        new FhrPassengerData(
            typeNo: 2,
            type: 'Child',
            title: 'Miss',
            firstName: 'Emma',
            lastName: 'Smith',
        ),
    ];

    $result = $service->submitLoungeOrder('cart-123', $customer, null, null, $additionalPassengers);

    expect($result->success)->toBeTrue()
        ->and($result->order)->toBe('lounge-order-789');

    // Assert the request was sent with passengers
    Http::assertSent(function ($request) {
        return $request['products'][0]['type'] === 'Lounge'
            && isset($request['products'][0]['additional_passengers']);
    });
});

it('submits lounge order without additional passengers when empty array provided', function () {
    Http::fake([
        'https://www.bookfhr.com/api/cart/cart-123/order/submit' => Http::response([
            'success' => true,
            'order' => 'lounge-order-solo',
            'message' => 'Single passenger lounge order created',
        ], 200),
    ]);

    $service = FhrCartService::make();
    $customer = FhrCustomerData::from([
        'title' => 'Ms',
        'firstName' => 'Alice',
        'lastName' => 'Jones',
        'email' => 'alice@example.com',
        'phone' => '+447999888777',
    ]);

    $result = $service->submitLoungeOrder('cart-123', $customer, null, null, []);

    expect($result->success)->toBeTrue()
        ->and($result->order)->toBe('lounge-order-solo');

    Http::assertSent(function ($request) {
        $product = $request['products'][0] ?? [];

        return $product['type'] === 'Lounge'
            && ! isset($product['additional_passengers']);
    });
});

it('retrieves the checkout URL through the client', function () {
    config([
        'fhr.payment_url' => 'https://www.bookfhr.com/payment',
        'fhr.tenant' => 'acme',
    ]);

    Http::fake([
        'https://www.bookfhr.com/payment/checkout/acme/cart-123' => Http::response(
            "https://checkout.stripe.com/pay/cs_test_123\n",
            200,
        ),
    ]);

    $service = FhrCartService::make();
    $checkout = $service->getCheckoutUrl('cart-123');

    expect($checkout->success)->toBeTrue()
        ->and($checkout->checkoutUrl)->toBe('https://checkout.stripe.com/pay/cs_test_123');

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'payment/checkout/acme/cart-123')
            && $request->hasHeader('Authorization', 'Bearer test-token');
    });
});

it('fails checkout when the tenant is not configured', function () {
    config(['fhr.payment_url' => 'https://www.bookfhr.com/payment', 'fhr.tenant' => null]);

    Http::fake();

    $service = FhrCartService::make();
    $checkout = $service->getCheckoutUrl('cart-123');

    expect($checkout->success)->toBeFalse();
    Http::assertNothingSent();
});

it('fails checkout when FHR returns a non-URL body', function () {
    config([
        'fhr.payment_url' => 'https://www.bookfhr.com/payment',
        'fhr.tenant' => 'acme',
    ]);

    Http::fake([
        'https://www.bookfhr.com/payment/checkout/acme/cart-123' => Http::response('not-a-url', 200),
    ]);

    $service = FhrCartService::make();
    $checkout = $service->getCheckoutUrl('cart-123');

    expect($checkout->success)->toBeFalse();
});

it('handles failed order submission', function () {
    Http::fake([
        'https://www.bookfhr.com/api/cart/cart-123/order/submit' => Http::response([
            'success' => false,
            'error' => 'Payment declined',
        ], 200),
    ]);

    $service = FhrCartService::make();
    $customer = FhrCustomerData::from([
        'title' => 'Mr',
        'firstName' => 'Test',
        'lastName' => 'User',
        'email' => 'test@example.com',
        'phone' => '+447000000000',
    ]);

    $result = $service->submitOrder('cart-123', $customer, []);

    expect($result->success)->toBeFalse()
        ->and($result->error)->toBe('Payment declined');
});
