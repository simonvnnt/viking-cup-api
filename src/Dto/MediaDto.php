<?php


namespace App\Dto;

class MediaDto
{
    public function __construct(
        public int     $roundId,
        public ?int    $id = null,
        public ?bool   $selected = false,
        public ?string $pilotFollow = null
    )
    {
    }
}