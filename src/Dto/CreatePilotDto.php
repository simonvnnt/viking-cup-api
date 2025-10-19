<?php

namespace App\Dto;

class CreatePilotDto
{
    public function __construct(
        public int     $personId,
        public int     $eventId,
        public ?bool   $ffsaLicensee = null,
        public ?string $ffsaNumber = null,
        public array   $participations = [],
        public ?int    $number = null,
        public bool    $receiveWindscreenBand = false,
    )
    {}
}