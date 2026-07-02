<?php

namespace Mjoc1985\Fhr\Data;

use Spatie\LaravelData\Data;

class FhrCustomerData extends Data
{
    public function __construct(
        public string $title,
        public string $firstName,
        public string $lastName,
        public string $email,
        public string $phone,
    ) {}
}
