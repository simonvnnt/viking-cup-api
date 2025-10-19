<?php

namespace App\Dto;

class CreateSponsorDto
{
    public function __construct(
        // Sponsor
        public string $name,
        public ?string $description = null,
        public bool $displayWebsite = false,
        public ?string $alt = null,

        // Contact
        public ?int $contactId = null,

        // Sponsorships
        public array $sponsorships = [],

        // Links
        public array $links = []
    )
    {}
}