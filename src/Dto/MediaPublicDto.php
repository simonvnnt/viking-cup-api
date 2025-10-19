<?php

namespace App\Dto;

class MediaPublicDto
{
    public function __construct(
        public string $firstName,
        public string $lastName,
        public string $email,
        public string $phone,
        public ?string $instagram = null,
        public ?string $pilotFollow = null,
        public array $presence = [],
    )
    {}
}