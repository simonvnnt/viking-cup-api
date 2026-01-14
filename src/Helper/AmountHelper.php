<?php

namespace App\Helper;

use App\Entity\Accounting;
use App\Entity\Round;

readonly class AmountHelper
{
    public function getAccountingAmount(Accounting $accounting, ?Round $round): float|int
    {
        $amount = $accounting->getUnitPrice() * $accounting->getQuantity();

        if ($round !== null && $accounting->getEvent() !== null && $accounting->getRound() === null) {
            $amount /= $accounting->getEvent()->getRounds()->count();
        }

        return $amount;
    }

    public function getAmountWithoutFees(float|int $amount): float|int
    {
        // billetweb fees => 0.29 + 1%
        return $amount / 1.01 - 0.29;
    }
}