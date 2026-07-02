<?php

namespace Mjoc1985\Fhr\Data;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class FhrInventoryProductData extends Data
{
    /**
     * @param  array<string>|null  $salesMessages
     */
    public function __construct(
        #[MapInputName('carParkId')]
        public int $id,
        #[MapInputName('carParkName')]
        public string $name,
        #[MapInputName('carParkSafeName')]
        public ?string $safeName,
        public ?string $subHeading,
        public ?string $image,
        public ?string $introduction,
        public ?string $overview,
        public ?string $address,
        public ?string $latitude,
        public ?string $longitude,
        public ?string $airportDistance,
        public ?string $transferFrequency,
        public ?string $transferCharges,
        public ?string $arrivalProcedure,
        public ?string $departureProcedure,
        public ?string $securityMeasures,
        public ?string $additionalInformation,
        public ?string $directions,
        public bool $parkMark = false,
        public bool $isMeetAndGreet = false,
        public bool $securityBarrier = false,
        public bool $cctv = false,
        public bool $fullSecurity = false,
        public bool $floodlighting = false,
        public bool $largeFamilySuited = false,
        public bool $largeEquipmentSuited = false,
        public bool $keepKeys = false,
        public bool $outdoor = false,
        public ?array $salesMessages = null,
        public ?int $reviewCount = null,
        public ?int $reviewScore = null,
        public ?int $reviewWouldBookAgain = null,
        public ?float $priceFrom = null,
        // Location nested object - mapped manually
        public ?string $locationCode = null,
        public ?string $locationName = null,
        public ?string $locationSafeName = null,
    ) {}

    public static function fromArray(array $data): self
    {
        // Extract location fields from nested object
        $location = $data['location'] ?? [];

        return new self(
            id: $data['carParkId'] ?? $data['id'] ?? 0,
            name: $data['carParkName'] ?? $data['name'] ?? '',
            safeName: $data['carParkSafeName'] ?? $data['safeName'] ?? null,
            subHeading: $data['subHeading'] ?? null,
            image: $data['image'] ?? null,
            introduction: $data['introduction'] ?? null,
            overview: $data['overview'] ?? null,
            address: $data['address'] ?? null,
            latitude: $data['latitude'] ?? null,
            longitude: $data['longitude'] ?? null,
            airportDistance: $data['airportDistance'] ?? null,
            transferFrequency: $data['transferFrequency'] ?? null,
            transferCharges: $data['transferCharges'] ?? null,
            arrivalProcedure: $data['arrivalProcedure'] ?? null,
            departureProcedure: $data['departureProcedure'] ?? null,
            securityMeasures: $data['securityMeasures'] ?? null,
            additionalInformation: $data['additionalInformation'] ?? null,
            directions: $data['directions'] ?? null,
            parkMark: $data['parkMark'] ?? false,
            isMeetAndGreet: $data['isMeetAndGreet'] ?? false,
            securityBarrier: $data['securityBarrier'] ?? false,
            cctv: $data['cctv'] ?? false,
            fullSecurity: $data['fullSecurity'] ?? false,
            floodlighting: $data['floodlighting'] ?? false,
            largeFamilySuited: $data['largeFamilySuited'] ?? false,
            largeEquipmentSuited: $data['largeEquipmentSuited'] ?? false,
            keepKeys: $data['keepKeys'] ?? false,
            outdoor: $data['outdoor'] ?? false,
            salesMessages: $data['salesMessages'] ?? null,
            reviewCount: $data['reviewCount'] ?? null,
            reviewScore: $data['reviewScore'] ?? null,
            reviewWouldBookAgain: $data['reviewWouldBookAgain'] ?? null,
            priceFrom: $data['priceFrom'] ?? null,
            locationCode: $location['code'] ?? null,
            locationName: $location['name'] ?? null,
            locationSafeName: $location['safeName'] ?? null,
        );
    }

    public function getSellingPoints(): array
    {
        if (empty($this->salesMessages)) {
            return [];
        }

        return array_filter($this->salesMessages, fn ($message) => ! empty($message));
    }

    public function getDescription(): ?string
    {
        return $this->introduction;
    }
}
