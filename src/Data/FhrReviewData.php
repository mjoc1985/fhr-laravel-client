<?php

namespace Mjoc1985\Fhr\Data;

use Spatie\LaravelData\Data;

class FhrReviewData extends Data
{
    public function __construct(
        public int $score,
        public int $maxScore,
        public int $scoreCount,
        public ?int $rebookScore,
    ) {}

    public function getScoreOutOfTen(): float
    {
        return $this->score / 10;
    }
}
