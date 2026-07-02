<?php

namespace Mjoc1985\Fhr\Data;

use Carbon\Carbon;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\DateTimeInterfaceCast;
use Spatie\LaravelData\Data;

class FhrBookingData extends Data
{
    public function __construct(
        public string $bookingId,
        #[WithCast(DateTimeInterfaceCast::class)]
        public ?Carbon $dateCreated,
        public string $status,
        public ?string $bookingSource,
        public string $productType,
        public ?string $surname,
        public string $email,
        #[WithCast(DateTimeInterfaceCast::class)]
        public ?Carbon $arrivalDate,
        #[WithCast(DateTimeInterfaceCast::class)]
        public ?Carbon $returnDate,
        public ?string $product,
        public ?string $description,
        public ?string $airportCode,
        public ?string $paymentRef,
        public ?string $registration,
        public ?float $pricePaid,
    ) {}
}
