<?php

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Mjoc1985\Fhr\Data\FhrSearchResultData;
use Mjoc1985\Fhr\Services\FhrSearchService;

beforeEach(function () {
    config([
        'fhr.base_url' => 'https://www.bookfhr.com/api',
        'fhr.token' => 'test-token',
        'fhr.source' => 'TEST',
    ]);
});

it('can search for parking products', function () {
    Http::fake([
        'https://www.bookfhr.com/api/search*' => Http::response([
            'searchId' => 'test-search-id',
            'results' => [
                [
                    'featured' => true,
                    'product' => [
                        'id' => 3032,
                        'type' => 'Parking',
                        'name' => 'Test Car Park',
                        'displayName' => null,
                        'safeName' => 'test-car-park',
                        'location' => [
                            'code' => 'MAN',
                            'name' => 'Manchester',
                            'safeName' => 'manchester',
                            'latitude' => null,
                            'longitude' => null,
                        ],
                        'image' => 'test.jpg',
                        'terminals' => ['All Terminals'],
                        'parkingDetails' => [
                            'parkingType' => 0,
                            'parkingTypeName' => 'Self Park',
                            'parkMark' => true,
                            'tradingStandards' => false,
                            'distanceFromAirport' => '5-10 mins',
                            'isMeetAndGreet' => false,
                            'accessFee' => 0,
                            'canAmend' => true,
                            'nonRefundable' => false,
                            'securityBarrier' => true,
                            'cctv' => true,
                            'fullSecurity' => true,
                            'floodlighting' => true,
                            'largeFamilySuited' => true,
                            'largeEquipmentSuited' => false,
                            'keepKeys' => true,
                            'outdoor' => false,
                            'longitude' => '-2.26',
                            'latitude' => '53.37',
                        ],
                        'sellingPoints' => ['Secure parking', 'Short transfer'],
                    ],
                    'review' => [
                        'score' => 80,
                        'maxScore' => 0,
                        'scoreCount' => 10,
                        'rebookScore' => 80,
                    ],
                    'options' => [
                        [
                            'id' => 1,
                            'name' => 'Standard Option',
                            'available' => 'Available',
                            'price' => [
                                'currency' => 'GBP',
                                'amount' => 54.49,
                                'rateId' => '123',
                                'bookingFee' => 0,
                                'discount' => null,
                                'baseCurrency' => 'GBP',
                                'baseAmount' => 54.49,
                                'exchangeRate' => 1,
                            ],
                            'description' => null,
                            'image' => null,
                            'deepLink' => 'https://example.com',
                        ],
                    ],
                ],
            ],
        ], 200),
    ]);

    $service = FhrSearchService::make();
    $result = $service->searchParking(
        location: 'MAN',
        dateFrom: Carbon::parse('2025-12-28'),
        timeFrom: '09:00',
        dateTo: Carbon::parse('2026-01-04'),
        timeTo: '17:00',
        useCache: false,
    );

    expect($result)->toBeInstanceOf(FhrSearchResultData::class)
        ->and($result->searchId)->toBe('test-search-id')
        ->and($result->hasResults())->toBeTrue()
        ->and($result->results->count())->toBe(1);

    $product = $result->results->first();
    expect($product->name)->toBe('Test Car Park')
        ->and($product->type)->toBe('Parking')
        ->and($product->location->code)->toBe('MAN')
        ->and($product->featured)->toBeTrue()
        ->and($product->options->count())->toBe(1);

    $option = $product->getCheapestOption();
    expect($option)->not->toBeNull()
        ->and($option->price->amount)->toBe(54.49)
        ->and($option->isAvailable())->toBeTrue();

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'search')
            && $request->hasHeader('Authorization', 'Bearer test-token')
            && $request['type'] === 'Parking'
            && $request['source'] === 'TEST'
            && $request['location'] === 'MAN';
    });
});

it('can search for lounge products', function () {
    Http::fake([
        'https://www.bookfhr.com/api/search*' => Http::response([
            'searchId' => 'lounge-search-id',
            'results' => [],
        ], 200),
    ]);

    $service = FhrSearchService::make();
    $result = $service->searchLounges(
        location: 'LGW',
        dateFrom: Carbon::parse('2025-12-28'),
        timeFrom: '09:00',
        dateTo: Carbon::parse('2025-12-28'),
        timeTo: '12:00',
        adults: 2,
        children: 1,
        infants: 0,
        useCache: false,
    );

    expect($result->searchId)->toBe('lounge-search-id')
        ->and($result->hasResults())->toBeFalse();

    Http::assertSent(function ($request) {
        return $request['type'] === 'Lounge'
            && $request['adults'] === 2
            && $request['children'] === 1
            && $request['infants'] === 0;
    });
});

it('caches search results', function () {
    Http::fake([
        'https://www.bookfhr.com/api/search*' => Http::response([
            'searchId' => 'cached-search-id',
            'results' => [],
        ], 200),
    ]);

    $service = FhrSearchService::make();

    // First call
    $result1 = $service->searchParking(
        location: 'MAN',
        dateFrom: Carbon::parse('2025-12-28'),
        timeFrom: '09:00',
        dateTo: Carbon::parse('2026-01-04'),
        timeTo: '17:00',
        useCache: true,
    );

    // Second call - should use cache
    $result2 = $service->searchParking(
        location: 'MAN',
        dateFrom: Carbon::parse('2025-12-28'),
        timeFrom: '09:00',
        dateTo: Carbon::parse('2026-01-04'),
        timeTo: '17:00',
        useCache: true,
    );

    expect($result1->searchId)->toBe($result2->searchId);

    // Should only have made one HTTP call
    Http::assertSentCount(1);
});
