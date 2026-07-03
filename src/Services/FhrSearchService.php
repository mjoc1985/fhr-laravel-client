<?php

namespace Mjoc1985\Fhr\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;
use Mjoc1985\Fhr\Data\FhrSearchResultData;
use Mjoc1985\Fhr\Data\FhrSearchValidityData;
use Mjoc1985\Fhr\Enums\FhrProductType;
use Mjoc1985\Fhr\Exceptions\FhrException;
use Mjoc1985\Fhr\FhrClient;

class FhrSearchService
{
    public function __construct(
        private readonly FhrClient $client,
        private readonly int $cacheTtlMinutes = 15,
    ) {}

    public static function make(bool $sandbox = false): self
    {
        return new self(
            client: FhrClient::make($sandbox),
            cacheTtlMinutes: config('fhr.cache.search_ttl_minutes', 15),
        );
    }

    /**
     * Search for parking products.
     *
     * @param  string|null  $sourceOverride  Override the default FHR source code (e.g., 'SPS10' for 10% discount)
     */
    public function searchParking(
        string $location,
        Carbon $dateFrom,
        string $timeFrom,
        Carbon $dateTo,
        string $timeTo,
        string $currency = 'GBP',
        bool $useCache = true,
        ?string $sourceOverride = null,
    ): FhrSearchResultData {
        return $this->search(
            type: FhrProductType::Parking,
            location: $location,
            dateFrom: $dateFrom,
            timeFrom: $timeFrom,
            dateTo: $dateTo,
            timeTo: $timeTo,
            currency: $currency,
            useCache: $useCache,
            sourceOverride: $sourceOverride,
        );
    }

    /**
     * Search for lounge products.
     *
     * @param  string|null  $sourceOverride  Override the default FHR source code (e.g., 'SPS10' for 10% discount)
     */
    public function searchLounges(
        string $location,
        Carbon $dateFrom,
        string $timeFrom,
        Carbon $dateTo,
        string $timeTo,
        int $adults,
        int $children = 0,
        int $infants = 0,
        string $currency = 'GBP',
        bool $useCache = true,
        ?string $sourceOverride = null,
    ): FhrSearchResultData {
        return $this->search(
            type: FhrProductType::Lounge,
            location: $location,
            dateFrom: $dateFrom,
            timeFrom: $timeFrom,
            dateTo: $dateTo,
            timeTo: $timeTo,
            currency: $currency,
            adults: $adults,
            children: $children,
            infants: $infants,
            useCache: $useCache,
            sourceOverride: $sourceOverride,
        );
    }

    /**
     * Generic search method.
     *
     * @param  string|null  $sourceOverride  Override the default FHR source code (e.g., 'SPS10' for 10% discount)
     *
     * @throws InvalidArgumentException
     */
    public function search(
        FhrProductType $type,
        string $location,
        Carbon $dateFrom,
        string $timeFrom,
        Carbon $dateTo,
        string $timeTo,
        string $currency = 'GBP',
        ?int $adults = null,
        ?int $children = null,
        ?int $infants = null,
        bool $useCache = true,
        ?string $sourceOverride = null,
    ): FhrSearchResultData {
        $this->validateSearchParams($location, $dateFrom, $timeFrom, $dateTo, $timeTo);

        $params = [
            'type' => $type->value,
            'source' => $sourceOverride ?? $this->client->getSource(),
            'location' => strtoupper($location),
            'dateFrom' => $dateFrom->format('Y-m-d'),
            'timeFrom' => $timeFrom,
            'dateTo' => $dateTo->format('Y-m-d'),
            'timeTo' => $timeTo,
            'currency' => $currency,
        ];

        // Add passenger counts for lounges
        if ($type === FhrProductType::Lounge) {
            $params['adults'] = $adults ?? 1;
            $params['children'] = $children ?? 0;
            $params['infants'] = $infants ?? 0;
        }

        $cacheKey = $this->getCacheKey($params);

        if ($useCache && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $response = $this->client->get('search', $params);
        $result = $this->parseSearchResponse($response);

        if ($useCache) {
            Cache::put($cacheKey, $result, now()->addMinutes($this->cacheTtlMinutes));
        }

        return $result;
    }

    /**
     * Retrieve previously cached search results by search ID.
     *
     * FHR stores search results with a limited TTL; this returns them without
     * re-running the search.
     */
    public function getSearchById(string $searchId): FhrSearchResultData
    {
        $response = $this->client->get("search/{$searchId}");

        return $this->parseSearchResponse($response);
    }

    /**
     * Check whether a search session is still valid (not expired).
     *
     * An expired search returns HTTP 410 from FHR, which is surfaced here as
     * an "expired" result rather than a thrown exception.
     */
    public function checkSearch(string $searchId): FhrSearchValidityData
    {
        try {
            $response = $this->client->get("search-check/{$searchId}");
        } catch (FhrException $e) {
            if ($e->getCode() === 410) {
                return FhrSearchValidityData::expired($searchId);
            }

            throw $e;
        }

        return new FhrSearchValidityData(
            searchId: $response['searchId'] ?? $searchId,
            valid: (bool) ($response['valid'] ?? false),
            status: $response['status'] ?? 'unknown',
        );
    }

    /**
     * Clear the search cache for specific parameters.
     */
    public function clearCache(array $params): void
    {
        $cacheKey = $this->getCacheKey($params);
        Cache::forget($cacheKey);
    }

    /**
     * Validate search parameters.
     *
     * @throws InvalidArgumentException
     */
    private function validateSearchParams(
        string $location,
        Carbon $dateFrom,
        string $timeFrom,
        Carbon $dateTo,
        string $timeTo,
    ): void {
        if (strlen($location) !== 3) {
            throw new InvalidArgumentException('Location must be a 3-letter airport code (e.g., MAN, LGW)');
        }

        if ($dateTo->lt($dateFrom)) {
            throw new InvalidArgumentException('Return date must be on or after departure date');
        }

        if (! preg_match('/^\d{2}:\d{2}$/', $timeFrom)) {
            throw new InvalidArgumentException('Departure time must be in HH:MM format');
        }

        if (! preg_match('/^\d{2}:\d{2}$/', $timeTo)) {
            throw new InvalidArgumentException('Return time must be in HH:MM format');
        }
    }

    private function parseSearchResponse(array $response): FhrSearchResultData
    {
        // Transform the nested 'product' structure into flat product data
        $results = collect($response['results'] ?? [])->map(function (array $item) {
            $product = $item['product'] ?? $item;
            $product['featured'] = $item['featured'] ?? false;
            $product['review'] = $item['review'] ?? null;
            $product['options'] = $item['options'] ?? [];

            return $product;
        })->all();

        return FhrSearchResultData::from([
            'searchId' => $response['searchId'] ?? '',
            'results' => $results,
        ]);
    }

    private function getCacheKey(array $params): string
    {
        ksort($params);
        $hash = md5(json_encode($params));
        $env = $this->client->isSandbox() ? 'sandbox' : 'production';

        return "fhr_search:{$env}:{$hash}";
    }
}
