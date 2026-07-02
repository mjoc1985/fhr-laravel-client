<?php

namespace Mjoc1985\Fhr\Data;

use Spatie\LaravelData\Data;

/**
 * Result of checking/refreshing an FHR search session.
 *
 * Used to communicate the state of a search refresh to the frontend,
 * including whether prices have changed and what action the user needs to take.
 */
class FhrSearchRefreshResult extends Data
{
    public function __construct(
        /** Whether the refresh was successful */
        public bool $success,

        /** Whether a refresh was actually needed */
        public bool $refreshNeeded,

        /** Whether the product is still available */
        public bool $productAvailable,

        /** Whether the price has changed from the original cart price */
        public bool $priceChanged,

        /** Original cart price in pence */
        public int $oldPrice,

        /** New price from FHR in pence (0 if product unavailable) */
        public int $newPrice,

        /** Price difference in pence (positive = increase, negative = decrease) */
        public int $priceDifference,

        /** Original booking fee in pence */
        public int $oldBookingFee,

        /** New booking fee from FHR in pence */
        public int $newBookingFee,

        /** Updated provider metadata (null if product unavailable) */
        public ?ProviderMetaData $providerMeta = null,

        /** Error message if refresh failed */
        public ?string $error = null,
    ) {}

    /**
     * Create a result indicating no refresh was needed.
     */
    public static function noRefreshNeeded(int $currentPrice, int $bookingFee): self
    {
        return new self(
            success: true,
            refreshNeeded: false,
            productAvailable: true,
            priceChanged: false,
            oldPrice: $currentPrice,
            newPrice: $currentPrice,
            priceDifference: 0,
            oldBookingFee: $bookingFee,
            newBookingFee: $bookingFee,
        );
    }

    /**
     * Create a result for a successful refresh with no price change.
     */
    public static function refreshedNoPriceChange(
        int $price,
        int $bookingFee,
        ProviderMetaData $providerMeta,
    ): self {
        return new self(
            success: true,
            refreshNeeded: true,
            productAvailable: true,
            priceChanged: false,
            oldPrice: $price,
            newPrice: $price,
            priceDifference: 0,
            oldBookingFee: $bookingFee,
            newBookingFee: $bookingFee,
            providerMeta: $providerMeta,
        );
    }

    /**
     * Create a result for a successful refresh with price change.
     */
    public static function refreshedWithPriceChange(
        int $oldPrice,
        int $newPrice,
        int $oldBookingFee,
        int $newBookingFee,
        ProviderMetaData $providerMeta,
    ): self {
        return new self(
            success: true,
            refreshNeeded: true,
            productAvailable: true,
            priceChanged: true,
            oldPrice: $oldPrice,
            newPrice: $newPrice,
            priceDifference: $newPrice - $oldPrice,
            oldBookingFee: $oldBookingFee,
            newBookingFee: $newBookingFee,
            providerMeta: $providerMeta,
        );
    }

    /**
     * Create a result indicating the product is no longer available.
     */
    public static function productUnavailable(int $oldPrice, int $oldBookingFee): self
    {
        return new self(
            success: true,
            refreshNeeded: true,
            productAvailable: false,
            priceChanged: false,
            oldPrice: $oldPrice,
            newPrice: 0,
            priceDifference: 0,
            oldBookingFee: $oldBookingFee,
            newBookingFee: 0,
            error: 'This product is no longer available for your selected dates.',
        );
    }

    /**
     * Create a result for a failed refresh.
     */
    public static function failed(int $oldPrice, int $oldBookingFee, string $error): self
    {
        return new self(
            success: false,
            refreshNeeded: true,
            productAvailable: false,
            priceChanged: false,
            oldPrice: $oldPrice,
            newPrice: 0,
            priceDifference: 0,
            oldBookingFee: $oldBookingFee,
            newBookingFee: 0,
            error: $error,
        );
    }

    /**
     * Check if user action is required (price change or product unavailable).
     */
    public function requiresUserAction(): bool
    {
        return $this->priceChanged || ! $this->productAvailable;
    }

    /**
     * Get formatted price difference for display.
     */
    public function getFormattedPriceDifference(): string
    {
        $amount = abs($this->priceDifference) / 100;
        $prefix = $this->priceDifference > 0 ? '+' : '-';

        return $prefix.'£'.number_format($amount, 2);
    }
}
