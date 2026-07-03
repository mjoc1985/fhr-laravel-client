<?php

namespace Mjoc1985\Fhr\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Mjoc1985\Fhr\Data\FhrInventoryLoungeData;
use Mjoc1985\Fhr\Data\FhrInventoryProductData;
use Mjoc1985\Fhr\Enums\FhrProductType;
use Mjoc1985\Fhr\FhrClient;

class FhrInventoryService
{
    public function __construct(
        private readonly FhrClient $client,
        private readonly int $cacheTtlMinutes = 1440, // 24 hours
    ) {}

    public static function make(bool $sandbox = false): self
    {
        return new self(
            client: FhrClient::make($sandbox),
            cacheTtlMinutes: config('fhr.cache.inventory_ttl_minutes', 1440),
        );
    }

    /**
     * Get a parking product by ID from the inventory.
     *
     * Endpoint: /inventory/parking/{id}
     */
    public function getParkingProduct(int $productId, bool $useCache = true): ?FhrInventoryProductData
    {
        return $this->getProductById(FhrProductType::Parking, $productId, $useCache);
    }

    /**
     * Get a lounge product by ID from the inventory.
     *
     * Endpoint: /inventory/lounge/{id}
     */
    public function getLoungeProduct(int $productId, bool $useCache = true): ?FhrInventoryLoungeData
    {
        $inventoryPath = FhrProductType::Lounge->inventoryPath();
        $cacheKey = $this->getProductCacheKey($inventoryPath, $productId);

        if ($useCache && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $response = $this->client->get("inventory/{$inventoryPath}/{$productId}");
        $product = $this->parseLoungeResponse($response);

        if ($useCache && $product) {
            Cache::put($cacheKey, $product, now()->addMinutes($this->cacheTtlMinutes));
        }

        return $product;
    }

    /**
     * Get multiple lounge products by their IDs.
     *
     * @param  array<int>  $productIds
     * @return Collection<int, FhrInventoryLoungeData>
     */
    public function getLoungeProductsByIds(array $productIds, bool $useCache = true): Collection
    {
        return collect($productIds)
            ->map(fn (int $id) => $this->getLoungeProduct($id, $useCache))
            ->filter()
            ->values();
    }

    /**
     * Get all hotel products from the inventory.
     *
     * Endpoint: /inventory/hotels (returns all hotels)
     *
     * @return Collection<int, FhrInventoryProductData>
     */
    public function getHotelProducts(bool $useCache = true): Collection
    {
        $cacheKey = $this->getCacheKey(FhrProductType::Hotel->inventoryPath());

        if ($useCache && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $response = $this->client->get('inventory/hotels');
        $products = $this->parseListResponse($response);

        if ($useCache) {
            Cache::put($cacheKey, $products, now()->addMinutes($this->cacheTtlMinutes));
        }

        return $products;
    }

    /**
     * Get a single product by ID and type.
     *
     * For parking/lounge: calls /inventory/{type}/{id}
     * For hotels: fetches all and filters by ID
     */
    public function getProductById(FhrProductType $type, int $productId, bool $useCache = true): ?FhrInventoryProductData
    {
        // Hotels endpoint returns all products, so we filter locally
        if ($type === FhrProductType::Hotel) {
            $products = $this->getHotelProducts($useCache);

            return $products->first(fn (FhrInventoryProductData $product) => $product->id === $productId);
        }

        // Parking and lounge require ID in the endpoint
        $inventoryPath = $type->inventoryPath();
        $cacheKey = $this->getProductCacheKey($inventoryPath, $productId);

        if ($useCache && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $response = $this->client->get("inventory/{$inventoryPath}/{$productId}");
        $product = $this->parseSingleResponse($response);

        if ($useCache && $product) {
            Cache::put($cacheKey, $product, now()->addMinutes($this->cacheTtlMinutes));
        }

        return $product;
    }

    /**
     * Get multiple products by their IDs.
     *
     * @param  array<int>  $productIds
     * @return Collection<int, FhrInventoryProductData>
     */
    public function getProductsByIds(FhrProductType $type, array $productIds, bool $useCache = true): Collection
    {
        return collect($productIds)
            ->map(fn (int $id) => $this->getProductById($type, $id, $useCache))
            ->filter()
            ->values();
    }

    /**
     * Clear the inventory cache.
     */
    public function clearCache(?FhrProductType $type = null, ?int $productId = null): void
    {
        if ($type && $productId) {
            Cache::forget($this->getProductCacheKey($type->inventoryPath(), $productId));
        } elseif ($type) {
            Cache::forget($this->getCacheKey($type->inventoryPath()));
        } else {
            Cache::forget($this->getCacheKey('hotels'));
        }
    }

    /**
     * Parse a list response (hotels endpoint) into a collection of products.
     *
     * @return Collection<int, FhrInventoryProductData>
     */
    private function parseListResponse(array $response): Collection
    {
        $products = $response['products'] ?? $response['results'] ?? $response;

        if (! is_array($products)) {
            return collect();
        }

        return collect($products)->map(
            fn (array $product) => FhrInventoryProductData::fromArray($product)
        )->values();
    }

    /**
     * Parse a single product response (parking/lounge endpoints).
     */
    private function parseSingleResponse(array $response): ?FhrInventoryProductData
    {
        // The response might be the product directly or wrapped
        $product = $response['product'] ?? $response;

        // Check for carParkId (parking) or id (other types)
        if (empty($product) || (! isset($product['carParkId']) && ! isset($product['id']))) {
            return null;
        }

        return FhrInventoryProductData::fromArray($product);
    }

    private function getCacheKey(string $type): string
    {
        return "fhr_inventory:{$this->environment()}:{$type}";
    }

    private function getProductCacheKey(string $type, int $productId): string
    {
        return "fhr_inventory:{$this->environment()}:{$type}:{$productId}";
    }

    private function environment(): string
    {
        return $this->client->isSandbox() ? 'sandbox' : 'production';
    }

    /**
     * Parse a lounge product response.
     */
    private function parseLoungeResponse(array $response): ?FhrInventoryLoungeData
    {
        // Check for loungeId to confirm this is a lounge response
        if (empty($response) || ! isset($response['loungeId'])) {
            return null;
        }

        return FhrInventoryLoungeData::fromArray($response);
    }
}
