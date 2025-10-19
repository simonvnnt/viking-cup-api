<?php

namespace App\Dto;

class CommissaireDto
{
    public function __construct(
        public int $roundId,
        public ?int $id = null,
        public ?string $licenceNumber = null,
        public ?string $asaCode = null,
        public ?int $typeId = null,
        public bool $isFlag = false,
    )
    {}
}