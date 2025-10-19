<?php

namespace App\Dto;

class CreateMemberDto
{
    public function __construct(
        public int $personId,
        public ?string $roleAsso = null,
        public ?string $roleVcup = null
    )
    {}
}