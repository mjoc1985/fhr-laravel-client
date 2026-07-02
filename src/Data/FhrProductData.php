<?php

namespace Mjoc1985\Fhr\Data;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

class FhrProductData extends Data
{
    /**
     * @param  DataCollection<int, FhrProductOptionData>  $options
     * @param  array<string>  $terminals
     * @param  array<string>|null  $sellingPoints
     */
    public function __construct(
        public int $id,
        public string $type,
        public string $name,
        public ?string $displayName,
        public ?string $safeName,
        public FhrLocationData $location,
        public ?string $image,
        public array $terminals,
        public ?FhrParkingDetailsData $parkingDetails,
        public ?FhrReviewData $review,
        #[DataCollectionOf(FhrProductOptionData::class)]
        public DataCollection $options,
        public ?array $sellingPoints,
        public bool $featured = false,
    ) {}

    public function getCheapestOption(): ?FhrProductOptionData
    {
        $available = $this->options->toCollection()->filter(fn (FhrProductOptionData $option) => $option->isAvailable());

        if ($available->count() === 0) {
            return null;
        }

        return $available->sortBy(fn (FhrProductOptionData $option) => $option->price->amount)->first();
    }

    public function isMeetAndGreet(): bool
    {
        return $this->parkingDetails !== null && $this->parkingDetails->isMeetAndGreet;
    }

    public function getTerminalsString(): string
    {
        return implode(', ', $this->terminals);
    }
}
