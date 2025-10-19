<?php

namespace App\Dto;

class MemberDto
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
        public ?string $roleAsso = null,
        public ?string $roleVcup = null
    )
    {}
}