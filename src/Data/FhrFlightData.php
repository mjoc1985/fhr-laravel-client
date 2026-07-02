<?php

namespace Mjoc1985\Fhr\Data;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;

class FhrFlightData extends Data
{
    public function __construct(
        #[MapInputName('outbound_flight')]
        #[MapOutputName('outbound_flight')]
        public ?string $outboundFlight,
        #[MapInputName('inbound_flight')]
        #[MapOutputName('inbound_flight')]
        public ?string $inboundFlight,
        #[MapInputName('outbound_terminal')]
        #[MapOutputName('outbound_terminal')]
        public ?string $outboundTerminal,
        #[MapInputName('inbound_terminal')]
        #[MapOutputName('inbound_terminal')]
        public ?string $inboundTerminal,
    ) {}
}
