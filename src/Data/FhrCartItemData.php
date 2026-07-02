<?php

namespace Mjoc1985\Fhr\Data;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

class FhrCartItemData extends Data
{
    /**
     * @param  DataCollection<int, FhrSupplementData>  $supplements
     * @param  DataCollection<int, FhrSupplementData>  $bookingSupplements
     */
    public function __construct(
        public string $itemId,
        public string $searchId,
        public string $name,
        public string $type,
        public string $productId,
        public string $optionId,
        public int $quantity,
        public float $price,
        public string $currency,
        public ?float $bookingFee,
        public ?string $rateId,
        #[DataCollectionOf(FhrSupplementData::class)]
        public DataCollection $supplements,
        #[DataCollectionOf(FhrSupplementData::class)]
        public DataCollection $bookingSupplements,
        public ?string $image = null,
        public ?float $basePrice = null,
        public string $baseCurrency = 'GBP',
    ) {}

    public function getPriceInPence(): int
    {
        return (int) round($this->price * 100);
    }

    public function getBookingFeeInPence(): int
    {
        return (int) round(($this->bookingFee ?? 0) * 100);
    }

    /**
     * Get total supplements amount.
     */
    public function getSupplementsTotal(): float
    {
        $total = 0.0;

        foreach ($this->supplements as $supplement) {
            $total += $supplement->price;
        }

        foreach ($this->bookingSupplements as $supplement) {
            $total += $supplement->price;
        }

        return $total;
    }

    public function getSupplementsTotalInPence(): int
    {
        return (int) round($this->getSupplementsTotal() * 100);
    }

    /**
     * Create from FHR API response item object.
     */
    public static function fromApiResponse(array $item): self
    {
        $price = $item['price'] ?? [];
        $checkout = $item['checkout'] ?? [];
        $bookings = $checkout['bookings'] ?? [];
        $firstBooking = $bookings[0] ?? [];

        // Extract cart-level supplements as Data objects
        $cartSupplements = collect($checkout['supplements'] ?? [])
            ->map(fn (array $s) => FhrSupplementData::fromApiResponse($s))
            ->all();

        // Extract booking-level supplements as Data objects
        $bookingSupplements = collect($firstBooking['supplements'] ?? [])
            ->map(fn (array $s) => FhrSupplementData::fromApiResponse($s))
            ->all();

        return new self(
            itemId: $item['_id'] ?? '',
            searchId: $item['searchId'] ?? '',
            name: $item['name'] ?? '',
            type: $item['type'] ?? 'Parking',
            productId: (string) ($item['product'] ?? ''),
            optionId: (string) ($item['option'] ?? ''),
            quantity: (int) ($item['quantity'] ?? 1),
            price: (float) ($price['amount'] ?? 0),
            currency: $price['currency'] ?? 'GBP',
            bookingFee: isset($price['bookingFee']) ? (float) $price['bookingFee'] : null,
            rateId: $price['rateId'] ?? null,
            supplements: new DataCollection(FhrSupplementData::class, $cartSupplements),
            bookingSupplements: new DataCollection(FhrSupplementData::class, $bookingSupplements),
            image: $item['image'] ?? null,
            basePrice: isset($price['baseAmount']) ? (float) $price['baseAmount'] : null,
            baseCurrency: $price['baseCurrency'] ?? 'GBP',
        );
    }
}
