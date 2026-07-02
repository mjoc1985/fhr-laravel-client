<?php

use Illuminate\Support\Facades\Http;
use Mjoc1985\Fhr\Exceptions\FhrAuthenticationException;
use Mjoc1985\Fhr\Exceptions\FhrRateLimitException;
use Mjoc1985\Fhr\Exceptions\FhrValidationException;
use Mjoc1985\Fhr\FhrClient;

beforeEach(function () {
    config([
        'fhr.base_url' => 'https://www.bookfhr.com/api',
        'fhr.token' => 'production-token',
        'fhr.sandbox_base_url' => 'https://bookfhr.dev/api',
        'fhr.sandbox_token' => 'sandbox-token',
        'fhr.source' => 'TEST',
    ]);
});

it('sends correct authorization header', function () {
    Http::fake([
        '*' => Http::response(['success' => true], 200),
    ]);

    $client = FhrClient::make();
    $client->get('test');

    Http::assertSent(function ($request) {
        return $request->hasHeader('Authorization', 'Bearer production-token')
            && $request->hasHeader('Accept', 'application/json');
    });
});

it('throws FhrAuthenticationException on 401', function () {
    Http::fake([
        '*' => Http::response(['error' => 'Unauthorized'], 401),
    ]);

    $client = FhrClient::make();
    $client->get('test');
})->throws(FhrAuthenticationException::class);

it('throws FhrAuthenticationException on 403', function () {
    Http::fake([
        '*' => Http::response(['error' => 'Forbidden'], 403),
    ]);

    $client = FhrClient::make();
    $client->get('test');
})->throws(FhrAuthenticationException::class);

it('throws FhrValidationException on 422', function () {
    Http::fake([
        '*' => Http::response([
            'error' => 'Validation failed',
            'errors' => ['field' => ['The field is required']],
        ], 422),
    ]);

    $client = FhrClient::make();
    $client->get('test');
})->throws(FhrValidationException::class);

it('throws FhrRateLimitException on 429', function () {
    Http::fake([
        '*' => Http::response(['error' => 'Too many requests'], 429),
    ]);

    $client = FhrClient::make();
    $client->get('test');
})->throws(FhrRateLimitException::class);

it('can make GET requests', function () {
    Http::fake([
        '*' => Http::response(['data' => 'test'], 200),
    ]);

    $client = FhrClient::make();
    $result = $client->get('search', ['type' => 'Parking']);

    expect($result)->toBe(['data' => 'test']);

    Http::assertSent(fn ($request) => $request->method() === 'GET');
});

it('can make POST requests', function () {
    Http::fake([
        '*' => Http::response(['cartId' => '123'], 200),
    ]);

    $client = FhrClient::make();
    $result = $client->post('cart/create', ['currency' => 'GBP']);

    expect($result)->toBe(['cartId' => '123']);

    Http::assertSent(fn ($request) => $request->method() === 'POST');
});

it('can make DELETE requests', function () {
    Http::fake([
        '*' => Http::response(['success' => true], 200),
    ]);

    $client = FhrClient::make();
    $result = $client->delete('partner/booking/cancel', ['bookingId' => '123']);

    expect($result)->toBe(['success' => true]);

    Http::assertSent(fn ($request) => $request->method() === 'DELETE');
});

it('returns the configured source code', function () {
    $client = FhrClient::make();

    expect($client->getSource())->toBe('TEST');
});

// =========================================================================
// Sandbox / Production Switching Tests
// =========================================================================

it('uses production URL when sandbox is disabled', function () {
    Http::fake([
        'https://www.bookfhr.com/*' => Http::response(['success' => true], 200),
    ]);

    $client = FhrClient::make(sandbox: false);
    $client->get('test');

    expect($client->isSandbox())->toBeFalse();

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'www.bookfhr.com')
            && $request->hasHeader('Authorization', 'Bearer production-token');
    });
});

it('uses sandbox URL when sandbox is enabled', function () {
    Http::fake([
        'https://bookfhr.dev/*' => Http::response(['success' => true], 200),
    ]);

    $client = FhrClient::make(sandbox: true);
    $client->get('test');

    expect($client->isSandbox())->toBeTrue();

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'bookfhr.dev')
            && $request->hasHeader('Authorization', 'Bearer sandbox-token');
    });
});

it('falls back to production token if sandbox token is not configured', function () {
    config(['fhr.sandbox_token' => null]);

    Http::fake([
        '*' => Http::response(['success' => true], 200),
    ]);

    $client = FhrClient::make(sandbox: true);
    $client->get('test');

    Http::assertSent(function ($request) {
        return $request->hasHeader('Authorization', 'Bearer production-token');
    });
});
