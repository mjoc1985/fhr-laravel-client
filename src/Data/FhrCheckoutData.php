<?php

namespace Mjoc1985\Fhr\Data;

use Spatie\LaravelData\Data;

/**
 * FHR Stripe Checkout response data.
 */
class FhrCheckoutData extends Data
{
    public function __construct(
        public bool $success,
        public ?string $checkoutUrl,
        public ?string $error,
    ) {}

    /**
     * Create a successful checkout response.
     */
    public static function success(string $checkoutUrl): self
    {
        return new self(
            success: true,
            checkoutUrl: $checkoutUrl,
            error: null,
        );
    }

    /**
     * Create a failed checkout response.
     */
    public static function failed(string $error): self
    {
        return new self(
            success: false,
            checkoutUrl: null,
            error: $error,
        );
    }
}
