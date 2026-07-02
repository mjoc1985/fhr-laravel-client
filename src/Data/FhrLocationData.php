<?php

namespace Mjoc1985\Fhr\Data;

use Spatie\LaravelData\Data;

class FhrLocationData extends Data
{
    public function __construct(
        public string $code,
        public string $name,
        public ?string $safeName,
        public ?float $latitude,
        public ?float $longitude,
    ) {}
}
