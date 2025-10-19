<?php

namespace App\Enum;

enum SponsorshipStatus: string
{
    // à contacter
    case TO_CONTACT = "À contacter";
    // à relancer
    case TO_REMIND = "À relancer";
    // en cours
    case IN_PROGRESS = "En cours";
    // refusé
    case REFUSED = "Refusé";
    // validé
    case VALIDATED = "Validé";
    // en attente du tiers
    case WAITING_FOR_THIRD_PARTY = "En attente du tiers";
    // bloqué
    case BLOCKED = "Bloqué";
}