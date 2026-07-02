<?php

use Illuminate\Support\Facades\Http;
use Mjoc1985\Fhr\Data\FhrInventoryLoungeData;
use Mjoc1985\Fhr\Data\FhrInventoryProductData;
use Mjoc1985\Fhr\Enums\FhrProductType;
use Mjoc1985\Fhr\Services\FhrInventoryService;

beforeEach(function () {
    config([
        'fhr.base_url' => 'https://www.bookfhr.com/api',
        'fhr.token' => 'test-token',
        'fhr.source' => 'TEST',
    ]);
});

it('can get a parking product by ID from inventory', function () {
    Http::fake([
        'https://www.bookfhr.com/api/inventory/parking/3032' => Http::response([
            'carParkId' => 3032,
            'carParkName' => 'Manchester Airport Parking',
            'carParkSafeName' => 'manchester-airport-parking',
            'subHeading' => 'Approved Car Parking at Manchester Airport',
            'image' => 'https://img-assets.bookfhr.com/parking/test.jpg',
            'introduction' => 'Convenient parking near the terminal',
            'overview' => '<p>Overview text</p>',
            'address' => '123 Airport Road<br />Manchester<br />M90 1QX',
            'latitude' => '53.37',
            'longitude' => '-2.26',
            'airportDistance' => '5-10 mins transfer',
            'transferFrequency' => 'On demand, 24/7',
            'transferCharges' => 'Free Shuttle 24/7',
            'arrivalProcedure' => 'Park and report to reception',
            'departureProcedure' => 'Call the car park on return',
            'securityMeasures' => 'CCTV, 24/7 staff, barrier access',
            'additionalInformation' => 'Premium spaces available',
            'directions' => 'Take M56 to Junction 4',
            'parkMark' => true,
            'isMeetAndGreet' => false,
            'securityBarrier' => true,
            'cctv' => true,
            'fullSecurity' => true,
            'floodlighting' => true,
            'largeFamilySuited' => true,
            'largeEquipmentSuited' => false,
            'keepKeys' => true,
            'outdoor' => false,
            'salesMessages' => [
                'Secure parking',
                'Short transfer',
                'Keep your keys',
                '',
            ],
            'reviewCount' => 150,
            'reviewScore' => 85,
            'reviewWouldBookAgain' => 90,
            'priceFrom' => 54.99,
            'location' => [
                'code' => 'MAN',
                'name' => 'Manchester',
                'safeName' => 'manchester',
            ],
        ], 200),
    ]);

    $service = FhrInventoryService::make();
    $product = $service->getParkingProduct(3032, useCache: false);

    expect($product)->toBeInstanceOf(FhrInventoryProductData::class)
        ->and($product->id)->toBe(3032)
        ->and($product->name)->toBe('Manchester Airport Parking')
        ->and($product->locationCode)->toBe('MAN')
        ->and($product->locationName)->toBe('Manchester')
        ->and($product->isMeetAndGreet)->toBeFalse()
        ->and($product->parkMark)->toBeTrue()
        ->and($product->cctv)->toBeTrue()
        ->and($product->getSellingPoints())->toHaveCount(3)
        ->and($product->getDescription())->toBe('Convenient parking near the terminal');

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'inventory/parking/3032')
            && $request->hasHeader('Authorization', 'Bearer test-token');
    });
});

it('can get a lounge product by ID from inventory', function () {
    Http::fake([
        'https://www.bookfhr.com/api/inventory/lounge/5001' => Http::response([
            'loungeId' => 5001,
            'loungeName' => 'Escape Lounge',
            'loungeSafeName' => 'escape-lounge',
            'image' => 'lounge.jpg',
            'introduction' => 'Premium lounge experience',
            'loungeFacilities' => 'WiFi, Refreshments',
            'terminal' => 'Terminal 1',
            'salesMessages' => ['Relaxing atmosphere', 'Complimentary refreshments'],
            'location' => [
                'code' => 'MAN',
                'name' => 'Manchester',
                'safeName' => 'manchester',
            ],
        ], 200),
    ]);

    $service = FhrInventoryService::make();
    $product = $service->getLoungeProduct(5001, useCache: false);

    expect($product)->toBeInstanceOf(FhrInventoryLoungeData::class)
        ->and($product->id)->toBe(5001)
        ->and($product->name)->toBe('Escape Lounge')
        ->and($product->locationCode)->toBe('MAN')
        ->and($product->terminal)->toBe('Terminal 1');

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'inventory/lounge/5001');
    });
});

it('can get all hotel products from inventory', function () {
    Http::fake([
        'https://www.bookfhr.com/api/inventory/hotels' => Http::response([
            [
                'carParkId' => 7001,
                'carParkName' => 'Airport Hotel',
                'carParkSafeName' => 'airport-hotel',
                'location' => [
                    'code' => 'MAN',
                    'name' => 'Manchester',
                    'safeName' => 'manchester',
                ],
            ],
            [
                'carParkId' => 7002,
                'carParkName' => 'Gatwick Hotel',
                'carParkSafeName' => 'gatwick-hotel',
                'location' => [
                    'code' => 'LGW',
                    'name' => 'London Gatwick',
                    'safeName' => 'london-gatwick',
                ],
            ],
        ], 200),
    ]);

    $service = FhrInventoryService::make();
    $products = $service->getHotelProducts(useCache: false);

    expect($products)->toHaveCount(2)
        ->and($products->first())->toBeInstanceOf(FhrInventoryProductData::class)
        ->and($products->first()->id)->toBe(7001);

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'inventory/hotels');
    });
});

it('can get multiple products by IDs', function () {
    Http::fake([
        'https://www.bookfhr.com/api/inventory/parking/3032' => Http::response([
            'carParkId' => 3032,
            'carParkName' => 'Manchester Airport Parking',
            'carParkSafeName' => 'manchester-airport-parking',
            'location' => [
                'code' => 'MAN',
                'name' => 'Manchester',
                'safeName' => 'manchester',
            ],
        ], 200),
        'https://www.bookfhr.com/api/inventory/parking/3033' => Http::response([
            'carParkId' => 3033,
            'carParkName' => 'Gatwick Airport Parking',
            'carParkSafeName' => 'gatwick-airport-parking',
            'location' => [
                'code' => 'LGW',
                'name' => 'London Gatwick',
                'safeName' => 'london-gatwick',
            ],
        ], 200),
    ]);

    $service = FhrInventoryService::make();
    $products = $service->getProductsByIds(FhrProductType::Parking, [3032, 3033], useCache: false);

    expect($products)->toHaveCount(2)
        ->and($products->first()->id)->toBe(3032)
        ->and($products->last()->id)->toBe(3033);

    Http::assertSentCount(2);
});

it('caches parking product results', function () {
    Http::fake([
        'https://www.bookfhr.com/api/inventory/parking/3032' => Http::response([
            'carParkId' => 3032,
            'carParkName' => 'Test Parking',
            'carParkSafeName' => 'test-parking',
            'location' => [
                'code' => 'MAN',
                'name' => 'Manchester',
                'safeName' => 'manchester',
            ],
        ], 200),
    ]);

    $service = FhrInventoryService::make();

    // First call
    $result1 = $service->getParkingProduct(3032, useCache: true);

    // Second call - should use cache
    $result2 = $service->getParkingProduct(3032, useCache: true);

    expect($result1->id)->toBe($result2->id);

    // Should only have made one HTTP call
    Http::assertSentCount(1);
});

it('handles product not found response', function () {
    Http::fake([
        'https://www.bookfhr.com/api/inventory/parking/9999' => Http::response([], 200),
    ]);

    $service = FhrInventoryService::make();
    $product = $service->getParkingProduct(9999, useCache: false);

    expect($product)->toBeNull();
});

it('handles empty hotels inventory response', function () {
    Http::fake([
        'https://www.bookfhr.com/api/inventory/hotels' => Http::response([], 200),
    ]);

    $service = FhrInventoryService::make();
    $products = $service->getHotelProducts(useCache: false);

    expect($products)->toHaveCount(0);
});
