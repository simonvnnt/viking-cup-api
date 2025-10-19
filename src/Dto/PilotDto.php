<?php

namespace App\Dto;

class PilotDto
{
    public function __construct(
        public string  $firstName,
        public string  $lastName,
        public string  $email,
        public string  $phone,
        public ?string $comment = null,
        public int     $warnings = 0,
        public ?string $instagram = null,
        public ?string $nationality = null,
        public array   $presence = [],
        public ?bool   $ffsaLicensee = null,
        public ?string $ffsaNumber = null,
        public ?int    $eventId = null,
        public ?int    $number = null,
        public bool    $receiveWindscreenBand = false,
    )
    {}
}