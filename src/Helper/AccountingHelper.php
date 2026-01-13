<?php

namespace App\Helper;

use App\Entity\Accounting;
use App\Entity\Round;

readonly class AccountingHelper
{
    public function getAccountAmount(Accounting $accounting, ?Round $round): float|int
    {
        $amount = $accounting->getUnitPrice() * $accounting->getQuantity();

        if ($round !== null && $accounting->getEvent() !== null && $accounting->getRound() === null) {
            $amount /= $accounting->getEvent()->getRounds()->count();
        }

        return $amount;
    }
}