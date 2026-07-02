<?php

namespace Mjoc1985\Fhr\Data;

class FhrSyncResult
{
    /**
     * @param  array<string>  $errors
     */
    public function __construct(
        public int $created = 0,
        public int $updated = 0,
        public int $unchanged = 0,
        public int $failed = 0,
        public int $airportsCreated = 0,
        public array $errors = [],
    ) {}

    /**
     * Merge another result into this one.
     */
    public function merge(FhrSyncResult $other): self
    {
        $this->created += $other->created;
        $this->updated += $other->updated;
        $this->unchanged += $other->unchanged;
        $this->failed += $other->failed;
        $this->airportsCreated += $other->airportsCreated;
        $this->errors = array_merge($this->errors, $other->errors);

        return $this;
    }

    /**
     * Get total products processed.
     */
    public function total(): int
    {
        return $this->created + $this->updated + $this->unchanged + $this->failed;
    }

    /**
     * Check if there were any errors.
     */
    public function hasErrors(): bool
    {
        return count($this->errors) > 0;
    }

    /**
     * Convert to array for display.
     *
     * @return array<string, int|array<string>>
     */
    public function toArray(): array
    {
        return [
            'created' => $this->created,
            'updated' => $this->updated,
            'unchanged' => $this->unchanged,
            'failed' => $this->failed,
            'airports_created' => $this->airportsCreated,
            'errors' => $this->errors,
        ];
    }
}
