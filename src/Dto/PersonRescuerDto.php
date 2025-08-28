<?php

namespace App\Dto;

class PersonRescuerDto
{
    public function __construct(
        public string $firstName,
        public string $lastName,
        public string $email,
        public string $phone,
        public ?string $comment = null,
        public int $warnings = 0,
        public array $rescuers = []
    )
    {}
}