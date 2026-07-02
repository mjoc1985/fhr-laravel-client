<?php

namespace Mjoc1985\Fhr\Data;

use Mjoc1985\Fhr\Enums\FhrStatusCheckOutcome;
use Spatie\LaravelData\Data;

class FhrStatusCheckResult extends Data
{
    public function __construct(
        public FhrStatusCheckOutcome $outcome,
        public ?string $fhrStatus = null,
        public ?string $message = null,
    ) {}

    public static function confirmed(string $fhrStatus): self
    {
        return new self(FhrStatusCheckOutcome::Confirmed, $fhrStatus);
    }

    public static function stillPending(string $fhrStatus): self
    {
        return new self(FhrStatusCheckOutcome::StillPending, $fhrStatus);
    }

    public static function alreadyConfirmed(): self
    {
        return new self(FhrStatusCheckOutcome::AlreadyConfirmed);
    }

    public static function notEligible(string $reason): self
    {
        return new self(FhrStatusCheckOutcome::NotEligible, message: $reason);
    }

    public static function failed(string $message): self
    {
        return new self(FhrStatusCheckOutcome::Failed, message: $message);
    }

    public function wasConfirmed(): bool
    {
        return $this->outcome === FhrStatusCheckOutcome::Confirmed;
    }
}
