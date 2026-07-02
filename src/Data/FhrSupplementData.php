<?php

namespace Mjoc1985\Fhr\Data;

use Spatie\LaravelData\Data;

class FhrSupplementData extends Data
{
    public function __construct(
        public string $name,
        public float $price,
        public string $currency,
        public ?string $reason = null,
    ) {}

    public function getPriceInPence(): int
    {
        return (int) round($this->price * 100);
    }

    /**
     * Create from FHR API response supplement object.
     */
    public static function fromApiResponse(array $supplement): self
    {
        return new self(
            name: $supplement['name'] ?? $supplement['reason'] ?? 'Supplement',
            price: (float) ($supplement['price'] ?? $supplement['amount'] ?? 0),
            currency: $supplement['currency'] ?? 'GBP',
            reason: $supplement['reason'] ?? null,
        );
    }
}
