<?php

namespace Mjoc1985\Fhr\Data;

use Spatie\LaravelData\Data;

class FhrDiscountData extends Data
{
    public function __construct(
        public bool $excluded,
        public float $grossPrice,
        public float $discountSaving,
        public float $discountAmount,
        public string $discountType,
        public ?string $discountDisplay,
        public ?float $grossPriceIncTaxes,
        public ?float $supplementsTotal,
    ) {}
}
