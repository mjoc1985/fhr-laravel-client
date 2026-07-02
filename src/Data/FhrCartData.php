<?php

namespace Mjoc1985\Fhr\Data;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

class FhrCartData extends Data
{
    /**
     * @param  DataCollection<int, FhrCartItemData>  $items
     */
    public function __construct(
        public string $cartId,
        #[DataCollectionOf(FhrCartItemData::class)]
        public DataCollection $items,
        public ?FhrCartTotalsData $totals = null,
        public string $currency = 'GBP',
    ) {}

    /**
     * Get all supplements from all items.
     *
     * @return array<FhrSupplementData>
     */
    public function getAllSupplements(): array
    {
        $supplements = [];

        foreach ($this->items as $item) {
            foreach ($item->supplements as $supplement) {
                $supplements[] = $supplement;
            }
            foreach ($item->bookingSupplements as $supplement) {
                $supplements[] = $supplement;
            }
        }

        return $supplements;
    }

    /**
     * Get total supplements amount across all items.
     */
    public function getSupplementsTotal(): float
    {
        $total = 0.0;

        foreach ($this->items as $item) {
            $total += $item->getSupplementsTotal();
        }

        return $total;
    }

    /**
     * Get total booking fees across all items.
     */
    public function getBookingFeesTotal(): float
    {
        $total = 0.0;

        foreach ($this->items as $item) {
            $total += $item->bookingFee ?? 0;
        }

        return $total;
    }

    /**
     * Create from FHR API cart response.
     */
    public static function fromApiResponse(array $response, string $cartId): self
    {
        $items = collect($response['items'] ?? [])
            ->map(fn (array $item) => FhrCartItemData::fromApiResponse($item))
            ->all();

        $totals = isset($response['totals'])
            ? FhrCartTotalsData::fromApiResponse($response['totals'])
            : null;

        return new self(
            cartId: $cartId,
            items: new DataCollection(FhrCartItemData::class, $items),
            totals: $totals,
            currency: $response['currency'] ?? 'GBP',
        );
    }
}
