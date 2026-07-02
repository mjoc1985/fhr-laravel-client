<?php

namespace Mjoc1985\Fhr\Enums;

enum FhrProductType: string
{
    case Parking = 'Parking';
    case Lounge = 'Lounge';
    case Hotel = 'Hotel';

    /**
     * Get the inventory endpoint path for this product type.
     */
    public function inventoryPath(): string
    {
        return match ($this) {
            self::Parking => 'parking',
            self::Lounge => 'lounge',
            self::Hotel => 'hotels',
        };
    }
}
