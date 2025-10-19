<?php

namespace App\Dto;

class SponsorLinkDto
{
    public function __construct(
        public int $linkTypeId,
        public string $link,
        public ?int $id = null,
    )
    {}
}