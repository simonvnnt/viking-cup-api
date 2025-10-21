<?php

namespace App\Dto;

class AccountingDto
{
    public function __construct(
        public ?int    $roundId = null,
        public ?int    $eventId = null,
        public ?string $name = null,
        public ?string $iteration = null,
        public ?int    $accountingTypeId = null,
        public ?float  $unitPrice = null,
        public ?int    $quantity = null,
        public ?string $date = null,
        public ?bool   $isDone = null,
        public ?bool   $isConfirmed = null,
        public ?string $comment = null
    )
    {}
}