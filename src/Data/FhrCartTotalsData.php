<?php

namespace Mjoc1985\Fhr\Data;

use Spatie\LaravelData\Data;

class FhrCartTotalsData extends Data
{
    public function __construct(
        public float $total,
        public string $currency,
        public float $baseTotal,
        public string $baseCurrency,
        public float $exchangeRate,
        public float $discountAmount,
        public float $discountSaving,
        public float $payNow,
        public float $payLater,
        public float $grandTotal,
        public ?float $totalIncPayOnArrival = null,
    ) {}

    public function getTotalInPence(): int
    {
        return (int) round($this->total * 100);
    }

    public function getGrandTotalInPence(): int
    {
        return (int) round($this->grandTotal * 100);
    }

    public function getPayNowInPence(): int
    {
        return (int) round($this->payNow * 100);
    }

    public function getPayLaterInPence(): int
    {
        return (int) round($this->payLater * 100);
    }

    /**
     * Create from FHR API response totals object.
     */
    public static function fromApiResponse(array $totals): self
    {
        return new self(
            total: (float) ($totals['total'] ?? 0),
            currency: $totals['currency'] ?? 'GBP',
            baseTotal: (float) ($totals['baseTotal'] ?? 0),
            baseCurrency: $totals['baseCurrency'] ?? 'GBP',
            exchangeRate: (float) ($totals['exchangeRate'] ?? 1),
            discountAmount: (float) ($totals['discountAmount'] ?? 0),
            discountSaving: (float) ($totals['discountSaving'] ?? 0),
            payNow: (float) ($totals['payNow'] ?? $totals['total'] ?? 0),
            payLater: (float) ($totals['payLater'] ?? 0),
            grandTotal: (float) ($totals['grandTotal'] ?? $totals['total'] ?? 0),
            totalIncPayOnArrival: isset($totals['totalIncPayOnArrival'])
                ? (float) $totals['totalIncPayOnArrival']
                : null,
        );
    }
}
