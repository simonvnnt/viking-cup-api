<?php

namespace App\Dto;

class CreateCommissaireDto
{
    public function __construct(
        // Person
        public int $personId,

        // Sponsorships
        public array $commissaires = []
    )
    {}
}