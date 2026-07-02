<?php

namespace Mjoc1985\Fhr\Data;

use Spatie\LaravelData\Data;

class FhrInventoryLoungeData extends Data
{
    /**
     * @param  array<string>|null  $salesMessages
     */
    public function __construct(
        public int $id,
        public string $name,
        public ?string $safeName,
        public ?string $image,
        public ?string $introduction,
        public ?string $loungeFacilities,
        public ?string $locationDetails,
        public ?string $directions,
        public ?string $terminal,
        public ?string $extraInfo,
        public ?string $seatingCapacity,
        public ?string $businessFacilities,
        public ?string $smoking,
        public bool $allowChild = true,
        public bool $allowInfant = true,
        public ?int $childAgeFrom = null,
        public ?int $childAgeTo = null,
        public ?string $childrenAllowedDescription = null,
        public ?array $salesMessages = null,
        // Opening hours
        public ?string $openMon = null,
        public ?string $closeMon = null,
        public ?string $openTue = null,
        public ?string $closeTue = null,
        public ?string $openWed = null,
        public ?string $closeWed = null,
        public ?string $openThu = null,
        public ?string $closeThu = null,
        public ?string $openFri = null,
        public ?string $closeFri = null,
        public ?string $openSat = null,
        public ?string $closeSat = null,
        public ?string $openSun = null,
        public ?string $closeSun = null,
        // Location
        public ?string $locationCode = null,
        public ?string $locationName = null,
        public ?string $locationSafeName = null,
    ) {}

    public static function fromArray(array $data): self
    {
        $location = $data['location'] ?? [];

        return new self(
            id: $data['loungeId'] ?? $data['id'] ?? 0,
            name: $data['loungeName'] ?? $data['name'] ?? '',
            safeName: $data['loungeSafeName'] ?? $data['safeName'] ?? null,
            image: $data['image'] ?? null,
            introduction: $data['introduction'] ?? null,
            loungeFacilities: $data['loungeFacilities'] ?? null,
            locationDetails: $data['locationDetails'] ?? null,
            directions: $data['directions'] ?? null,
            terminal: $data['terminal'] ?? null,
            extraInfo: $data['extraInfo'] ?? null,
            seatingCapacity: $data['seatingCapacity'] ?? null,
            businessFacilities: $data['businessFacilities'] ?? null,
            smoking: $data['smoking'] ?? null,
            allowChild: $data['allowChild'] ?? true,
            allowInfant: $data['allowInfant'] ?? true,
            childAgeFrom: $data['child_Age_From'] ?? null,
            childAgeTo: $data['child_Age_To'] ?? null,
            childrenAllowedDescription: $data['childrenAllowedStringDescription'] ?? null,
            salesMessages: $data['salesMessages'] ?? null,
            openMon: self::parseTime($data['openMon'] ?? null),
            closeMon: self::parseTime($data['closeMon'] ?? null),
            openTue: self::parseTime($data['openTue'] ?? null),
            closeTue: self::parseTime($data['closeTue'] ?? null),
            openWed: self::parseTime($data['openWed'] ?? null),
            closeWed: self::parseTime($data['closeWed'] ?? null),
            openThu: self::parseTime($data['openThu'] ?? null),
            closeThu: self::parseTime($data['closeThu'] ?? null),
            openFri: self::parseTime($data['openFri'] ?? null),
            closeFri: self::parseTime($data['closeFri'] ?? null),
            openSat: self::parseTime($data['openSat'] ?? null),
            closeSat: self::parseTime($data['closeSat'] ?? null),
            openSun: self::parseTime($data['openSun'] ?? null),
            closeSun: self::parseTime($data['closeSun'] ?? null),
            locationCode: $location['code'] ?? null,
            locationName: $location['name'] ?? null,
            locationSafeName: $location['safeName'] ?? null,
        );
    }

    /**
     * Parse time from FHR format (1900-01-01T04:30:00) to HH:MM.
     */
    private static function parseTime(?string $datetime): ?string
    {
        if (! $datetime) {
            return null;
        }

        // Extract time part from datetime string
        if (preg_match('/T(\d{2}:\d{2})/', $datetime, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Get selling points from sales messages.
     *
     * @return array<string>
     */
    public function getSellingPoints(): array
    {
        if (empty($this->salesMessages)) {
            return [];
        }

        return array_filter($this->salesMessages, fn ($message) => ! empty($message));
    }

    /**
     * Get opening hours as a structured array.
     *
     * @return array<string, array{open: string|null, close: string|null}>
     */
    public function getOpeningHours(): array
    {
        return [
            'monday' => ['open' => $this->openMon, 'close' => $this->closeMon],
            'tuesday' => ['open' => $this->openTue, 'close' => $this->closeTue],
            'wednesday' => ['open' => $this->openWed, 'close' => $this->closeWed],
            'thursday' => ['open' => $this->openThu, 'close' => $this->closeThu],
            'friday' => ['open' => $this->openFri, 'close' => $this->closeFri],
            'saturday' => ['open' => $this->openSat, 'close' => $this->closeSat],
            'sunday' => ['open' => $this->openSun, 'close' => $this->closeSun],
        ];
    }

    /**
     * Get the description (same as introduction for compatibility).
     */
    public function getDescription(): ?string
    {
        return $this->introduction;
    }
}
