<?php

namespace Mjoc1985\Fhr\Data;

use Spatie\LaravelData\Data;

class FhrPriceData extends Data
{
    public function __construct(
        public string $currency,
        public float $amount,
        public ?string $rateId,
        public ?float $bookingFee,
        public ?FhrDiscountData $discount,
        public ?string $baseCurrency,
        public ?float $baseAmount,
        public ?float $exchangeRate,
    ) {}

    public function getAmountInPence(): int
    {
        return (int) round($this->amount * 100);
    }
}
