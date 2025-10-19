<?php

namespace App\Dto;

class CreateVolunteerDto
{
    public function __construct(
        // Person
        public int $personId,
        public array $roundDetails = [],

        // Volunteers
        public array $volunteers = []
    )
    {}
}