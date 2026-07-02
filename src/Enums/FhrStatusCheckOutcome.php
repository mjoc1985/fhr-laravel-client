<?php

namespace Mjoc1985\Fhr\Enums;

enum FhrStatusCheckOutcome: string
{
    case Confirmed = 'confirmed';
    case StillPending = 'still_pending';
    case AlreadyConfirmed = 'already_confirmed';
    case NotEligible = 'not_eligible';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Confirmed => 'Booking confirmed',
            self::StillPending => 'FHR reports the order is still pending',
            self::AlreadyConfirmed => 'Booking is already confirmed',
            self::NotEligible => 'Not an FHR booking with an order ID',
            self::Failed => 'FHR check failed',
        };
    }
}
