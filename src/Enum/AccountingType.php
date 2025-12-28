<?php

namespace App\Enum;

enum AccountingType: string
{
    case INCOME = "income";
    case EXPENSE = "expense";
}