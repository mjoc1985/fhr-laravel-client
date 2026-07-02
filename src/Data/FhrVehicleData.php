<?php

namespace Mjoc1985\Fhr\Data;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;

class FhrVehicleData extends Data
{
    public function __construct(
        #[MapInputName('vehicle_reg')]
        #[MapOutputName('vehicle_reg')]
        public string $vehicleReg,
        #[MapInputName('vehicle_make')]
        #[MapOutputName('vehicle_make')]
        public ?string $vehicleMake,
        #[MapInputName('vehicle_model')]
        #[MapOutputName('vehicle_model')]
        public ?string $vehicleModel,
        public ?string $colour,
        public int $passengers = 1,
    ) {}
}
