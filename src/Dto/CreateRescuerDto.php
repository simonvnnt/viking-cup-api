<?php

namespace App\Dto;

class CreateRescuerDto
{
    public function __construct(
        // Person
        public int $personId,

        // Rescuers
        public array $rescuers = []
    )
    {}
}