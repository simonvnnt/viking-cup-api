<?php

namespace App\Dto;

use App\Enum\SponsorCounterpartType;

class SponsorshipCounterpartDto
{
    public function __construct(
        public SponsorCounterpartType $counterpartType,
        public ?int $id = null,
        public float|int|null $amount = null,
        public ?string $otherCounterpart = null
    )
    {}
}