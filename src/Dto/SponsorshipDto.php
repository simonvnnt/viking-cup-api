<?php

namespace App\Dto;

use App\Enum\SponsorshipStatus;

class SponsorshipDto
{
    public function __construct(
        public SponsorshipStatus $status,
        public ?int $id = null,
        public ?int $eventId = null,
        public ?int $roundId = null,
        public array $counterparts = []
    )
    {}
}