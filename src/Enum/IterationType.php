<?php

namespace App\Enum;

enum IterationType: string
{
    case ONE_SHOT = "one_shot";
    case ANNUAL = "annual";
    case SEMI_ANNUAL = "semi_annual";
    case QUARTERLY = "quarterly";
    case MONTHLY = "monthly";
    case WEEKLY = "weekly";
    case DAILY = "daily";
}