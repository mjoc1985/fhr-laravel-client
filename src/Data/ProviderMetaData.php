<?php

namespace Mjoc1985\Fhr\Data;

use Spatie\LaravelData\Data;

/**
 * Provider-specific metadata for external API products.
 *
 * This DTO holds data required for cart/booking operations with external providers.
 * Each provider (FHR, etc.) stores its required identifiers here.
 */
class ProviderMetaData extends Data
{
    /**
     * @param  string  $provider  Provider identifier (e.g., 'fhr', 'holiday_extras')
     * @param  string|null  $searchId  Provider's search/session ID
     * @param  string|null  $rateId  Provider's rate/price ID
     * @param  int|null  $externalProductId  Provider's product ID
     * @param  int|null  $optionId  Provider's option/variant ID
     * @param  array<string, mixed>  $extra  Additional provider-specific data
     */
    public function __construct(
        public string $provider,
        public ?string $searchId = null,
        public ?string $rateId = null,
        public ?int $externalProductId = null,
        public ?int $optionId = null,
        public array $extra = [],
    ) {}

    /**
     * Create metadata for an FHR product.
     */
    public static function forFhr(
        string $searchId,
        string $rateId,
        int $externalProductId,
        int $optionId,
    ): self {
        return new self(
            provider: 'fhr',
            searchId: $searchId,
            rateId: $rateId,
            externalProductId: $externalProductId,
            optionId: $optionId,
        );
    }

    /**
     * Create metadata for a Maple product.
     */
    public static function forMaple(string $sku): self
    {
        return new self(
            provider: 'maple',
            extra: ['sku' => $sku],
        );
    }

    /**
     * Create metadata for a ParkFly (GPT) product.
     */
    public static function forParkFly(string $productCode): self
    {
        return new self(
            provider: 'parkfly',
            extra: ['product_code' => $productCode],
        );
    }
}
