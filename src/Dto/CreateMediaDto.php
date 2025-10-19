<?php

namespace App\Dto;

class CreateMediaDto
{
    public function __construct(
        // Person
        public int $personId,
        public array $roundDetails = [],

        // Volunteers
        public array $medias = []
    )
    {}
}