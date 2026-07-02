<?php

namespace Mjoc1985\Fhr\Data;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

class FhrSearchResultData extends Data
{
    /**
     * @param  DataCollection<int, FhrProductData>  $results
     */
    public function __construct(
        public string $searchId,
        #[DataCollectionOf(FhrProductData::class)]
        public DataCollection $results,
    ) {}

    public function hasResults(): bool
    {
        return $this->results->count() > 0;
    }

    public function getCheapestProduct(): ?FhrProductData
    {
        if (! $this->hasResults()) {
            return null;
        }

        return $this->results->toCollection()
            ->filter(fn (FhrProductData $product) => $product->getCheapestOption() !== null)
            ->sortBy(fn (FhrProductData $product) => $product->getCheapestOption()?->price->amount)
            ->first();
    }

    public function getFeaturedProducts(): DataCollection
    {
        return $this->results->filter(fn (FhrProductData $product) => $product->featured);
    }
}
