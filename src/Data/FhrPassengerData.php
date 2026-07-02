<?php

namespace Mjoc1985\Fhr\Data;

use Spatie\LaravelData\Data;

/**
 * FHR passenger data for lounge API submissions.
 */
class FhrPassengerData extends Data
{
    public function __construct(
        public int $typeNo,
        public string $type,
        public string $title,
        public string $firstName,
        public string $lastName,
    ) {}
}
