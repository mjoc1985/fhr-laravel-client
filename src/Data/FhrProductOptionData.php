<?php

namespace Mjoc1985\Fhr\Data;

use Spatie\LaravelData\Data;

class FhrProductOptionData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public string $available,
        public FhrPriceData $price,
        public ?string $description,
        public ?string $image,
        public ?string $deepLink,
    ) {}

    public function isAvailable(): bool
    {
        return strtolower($this->available) === 'available';
    }
}
