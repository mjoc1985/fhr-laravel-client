<?php

namespace Mjoc1985\Fhr\Enums;

/**
 * FHR source codes for dynamic discount pricing.
 *
 * Each source code corresponds to a discount percentage tier.
 * The source code is sent to FHR API during search to apply
 * the appropriate discount to eligible products.
 */
enum FhrSourceCode: string
{
    case SPS0 = 'SPS0';   // 0% - No discount (base pricing)
    case SPS10 = 'SPS10'; // Up to 10% discount
    case SPS15 = 'SPS15'; // Up to 15% discount
    case SPS20 = 'SPS20'; // Up to 20% discount
    case SPS25 = 'SPS25'; // Up to 25% discount
    case SPS30 = 'SPS30'; // Up to 30% discount
    case SPS35 = 'SPS35'; // Up to 35% discount

    /**
     * Get the appropriate source code for a given discount percentage.
     *
     * When strict promotion matching is disabled (default), any discount
     * > 0% uses SPS30 to fetch the best available FHR pricing tier.
     * When strict, maps to the nearest tier.
     */
    public static function fromDiscountPercentage(int $percentage, bool $strict = false): self
    {
        if ($percentage <= 0) {
            return self::SPS0;
        }

        if (! $strict) {
            return self::SPS35;
        }

        return match (true) {
            $percentage <= 10 => self::SPS10,
            $percentage <= 15 => self::SPS15,
            $percentage <= 20 => self::SPS20,
            $percentage <= 25 => self::SPS25,
            $percentage <= 30 => self::SPS30,
            default => self::SPS35,
        };
    }

    /**
     * Get the maximum discount percentage for this source code.
     */
    public function maxDiscountPercentage(): int
    {
        return match ($this) {
            self::SPS0 => 0,
            self::SPS10 => 10,
            self::SPS15 => 15,
            self::SPS20 => 20,
            self::SPS25 => 25,
            self::SPS30 => 30,
            self::SPS35 => 35,
        };
    }
}
