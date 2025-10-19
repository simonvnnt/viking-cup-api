<?php

namespace App\Dto;

class CreateVisitorDto
{
    public function __construct(
        public int $personId,
        public int $roundDetailId,
        public int $companions = 0
    )
    {}
}