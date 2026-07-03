<?php

namespace Mjoc1985\Fhr\Data;

use Spatie\LaravelData\Data;

/**
 * Result of checking whether a search session is still valid.
 *
 * FHR searches expire after a TTL; an expired search returns HTTP 410, which
 * is normalised here into {@see self::$valid} = false with status "expired".
 */
class FhrSearchValidityData extends Data
{
    public function __construct(
        public string $searchId,
        public bool $valid,
        public string $status,
    ) {}

    public static function active(string $searchId): self
    {
        return new self($searchId, true, 'active');
    }

    public static function expired(string $searchId): self
    {
        return new self($searchId, false, 'expired');
    }

    public function isValid(): bool
    {
        return $this->valid;
    }
}
