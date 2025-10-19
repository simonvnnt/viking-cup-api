<?php

namespace App\Dto;

class PersonDto
{
    public function __construct(
        public string $firstName,
        public string $lastName,
        public string $email,
        public string $phone,
        public ?string $address = null,
        public ?string $city = null,
        public ?string $zipCode = null,
        public ?string $country = null,
        public ?string $instagram = null,
        public ?string $comment = null,
        public int $warnings = 0
    )
    {}
}