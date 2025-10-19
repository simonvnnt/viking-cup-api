<?php

namespace App\Dto;

class SponsorDto
{
    public function __construct(
        // Sponsor
        public string $name,
        public ?string $description = null,
        public bool $displayWebsite = false,
        public ?string $alt = null,

        // Contact
        public ?string $firstName = null,
        public ?string $lastName = null,
        public ?string $email = null,
        public ?string $phone = null,
        public int $warnings = 0,
        public ?string $comment = null,

        // Sponsorships
        public array $sponsorships = [],

        // Links
        public array $links = []
    )
    {}
}