# FHR Client

A standalone Laravel client for the **FHR API** — search, inventory, and cart/checkout for airport parking, lounges, and hotels.

A reusable, publishable package containing the low-level SDK only (HTTP client, DTOs, enums, exceptions). Application-specific concerns — syncing the catalogue into your database, pricing against your own products, persisting bookings — stay in your app and consume this package.

## Requirements

- PHP 8.4+
- Laravel 11 or 12

## Installation

```bash
composer require mjoc1985/fhr-laravel-client
```

The service provider is auto-discovered. Publish the config if you want to customise it:

```bash
php artisan vendor:publish --tag=fhr-config
```

## Configuration

All settings are environment-driven (`config/fhr.php`). The common ones:

```dotenv
FHR_API_URL=https://www.bookfhr.com/api
FHR_API_TOKEN=your-production-token
FHR_SOURCE_CODE=SPS0
FHR_TENANT=your-tenant

# Sandbox (used when you request sandbox mode explicitly)
FHR_SANDBOX_API_URL=https://bookfhr.dev/api
FHR_SANDBOX_API_TOKEN=your-sandbox-token
FHR_SANDBOX_TENANT=your-sandbox-tenant

# Optional: route the client's logs to a dedicated channel (defaults to your app's default)
FHR_LOG_CHANNEL=fhr
```

## Usage

### Search

```php
use Mjoc1985\Fhr\Services\FhrSearchService;
use Carbon\Carbon;

$search = FhrSearchService::make();

$results = $search->searchParking(
    location: 'MAN',
    dateFrom: Carbon::parse('2026-08-01'),
    timeFrom: '10:00',
    dateTo: Carbon::parse('2026-08-08'),
    timeTo: '18:00',
);

$cheapest = $results->getCheapestProduct();
```

### Inventory

```php
use Mjoc1985\Fhr\Services\FhrInventoryService;

$inventory = FhrInventoryService::make();
$product = $inventory->getParkingProduct(12345);
$lounge  = $inventory->getLoungeProduct(6789);
```

### Cart & checkout

```php
use Mjoc1985\Fhr\Services\FhrCartService;

$cart = FhrCartService::make();
// build cart, add items, retrieve the FHR-hosted checkout URL, etc.
```

### Low-level client

```php
use Mjoc1985\Fhr\FhrClient;

$client = FhrClient::make();               // production credentials
$sandbox = FhrClient::make(sandbox: true); // sandbox credentials

$response = $client->get('search', ['type' => 'Parking', /* ... */]);
```

Or resolve any of them from the container (`app(FhrClient::class)`), since the service provider binds them.

## Sandbox vs production

The package does not reach into your application's state to decide which credentials to use. **You** decide, and pass a boolean:

```php
$client  = FhrClient::make(sandbox: $someAppFlag);
$search  = FhrSearchService::make(sandbox: $someAppFlag);
```

A common pattern is to bind these in your own service provider, resolving the flag from wherever your app keeps its "test mode":

```php
$this->app->bind(FhrClient::class, fn () => FhrClient::make(sandbox: isTestMode()));
```

## Logging API calls

The client records each request/response through an `ApiLogger`. By default it uses a no-op (`NullApiLogger`), so nothing is persisted. To capture calls (e.g. to a database), implement the contract and bind it:

```php
use Mjoc1985\Fhr\Contracts\ApiLogger;

class DatabaseApiLogger implements ApiLogger
{
    public function log(/* ... */): void
    {
        // persist however you like
    }
}

// in a service provider
$this->app->bind(ApiLogger::class, DatabaseApiLogger::class);
```

## Testing

```bash
composer install
composer test      # Pest
composer lint      # Pint
composer analyse   # PHPStan (larastan)
```

## License

MIT. See [LICENSE](LICENSE).
