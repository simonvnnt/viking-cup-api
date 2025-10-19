<?php

namespace App\Dto;

class VolunteerDto
{
    public function __construct(
        public int $roundId,
        public ?int $id = null,
        public ?int $roleId = null
    )
    {}
}