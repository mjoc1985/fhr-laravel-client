<?php

namespace Mjoc1985\Fhr\Data;

use Spatie\LaravelData\Data;

class FhrOrderResponseData extends Data
{
    public function __construct(
        public bool $success,
        public ?string $order,
        public ?string $message,
        public ?string $error,
        public ?float $paidAmount = null,
        public ?float $refundAmount = null,
    ) {}

    public function getOrderId(): ?string
    {
        return $this->order;
    }
}
