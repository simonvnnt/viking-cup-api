<?php

namespace App\Dto;

class PilotPresenceDto
{
    public function __construct(
        public int    $roundId,
        public int    $categoryId,
        public string $vehicle
    )
    {}
}