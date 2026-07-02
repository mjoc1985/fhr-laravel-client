<?php

namespace Mjoc1985\Fhr\Data;

use Spatie\LaravelData\Data;

class FhrParkingDetailsData extends Data
{
    public function __construct(
        public int|string $parkingType,
        public string $parkingTypeName,
        public bool $parkMark,
        public bool $tradingStandards,
        public ?string $distanceFromAirport,
        public bool $isMeetAndGreet,
        public float $accessFee,
        public bool $canAmend,
        public bool $nonRefundable,
        public bool $securityBarrier,
        public bool $cctv,
        public bool $fullSecurity,
        public bool $floodlighting,
        public bool $largeFamilySuited,
        public bool $largeEquipmentSuited,
        public bool $keepKeys,
        public bool $outdoor,
        public ?string $longitude,
        public ?string $latitude,
    ) {}
}
