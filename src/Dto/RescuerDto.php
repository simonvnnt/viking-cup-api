<?php

namespace App\Dto;

class RescuerDto
{
    public function __construct(
        public int $roundId,
        public ?int $id = null,
        public ?string $role = null,
    )
    {}
}