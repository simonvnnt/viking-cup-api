<?php

namespace App\Business;

use App\Enum\AccountingType;
use App\Helper\AmountHelper;
use App\Repository\AccountingRepository;
use App\Repository\CategoryRepository;
use App\Repository\CommissaireRepository;
use App\Repository\EventRepository;
use App\Repository\MediaRepository;
use App\Repository\PilotRepository;
use App\Repository\RescuerRepository;
use App\Repository\RoundDetailRepository;
use App\Repository\RoundRepository;
use App\Repository\TicketRepository;
use App\Repository\VisitorRepository;
use App\Repository\VolunteerRepository;

readonly class KpiBusiness
{
    public function __construct(
        private PilotRepository $pilotRepository,
        private VisitorRepository $visitorRepository,
        private MediaRepository $mediaRepository,
        private CommissaireRepository $commissaireRepository,
        private VolunteerRepository $volunteerRepository,
        private RescuerRepository $rescuerRepository,
        private CategoryRepository $categoryRepository,
        private RoundDetailRepository $roundDetailRepository,
        private AccountingRepository $accountingRepository,
        private TicketRepository $ticketRepository,
        private EventRepository $eventRepository,
        private RoundRepository $roundRepository,
        private AmountHelper $amountHelper
    )
    {}

    public function getPilotsCount(?int $eventId = null, ?int $roundId = null): array
    {
        if ($eventId !== null) {
            $categories = $this->categoryRepository->findByEventId($eventId);
        } else {
            $categories = $this->categoryRepository->findAll();
        }
        $kpi = [];

        foreach ($categories as $category) {
            $pilotCount = $this->pilotRepository->countPilots($eventId, $roundId, $category);

            $kpi[] = [
                'category' => $category,
                'count' => $pilotCount,
            ];
        }

        return $kpi;
    }

    public function getVisitorsCount(?int $eventId = null, ?int $roundId = null): array
    {
        $roundDetails = $this->roundDetailRepository->findByEventAndRound($eventId, $roundId);
        $kpi = [];

        foreach ($roundDetails as $roundDetail) {
            $visitorCount = $this->visitorRepository->countVisitors($roundDetail);

            $kpi[] = [
                'roundDetail' => $roundDetail,
                'count' => $visitorCount,
            ];
        }

        return $kpi;
    }

    public function getMediasCount(?int $eventId = null, ?int $roundId = null): array
    {
        return [
            'count' => $this->mediaRepository->countMedias($eventId, $roundId)
        ];
    }

    public function getCommissairesCount(?int $eventId = null, ?int $roundId = null): array
    {
        return [
            'count' => $this->commissaireRepository->countCommissaires($eventId, $roundId)
        ];
    }

    public function getVolunteerCount(?int $eventId = null, ?int $roundId = null): array
    {
        return [
            'count' => $this->volunteerRepository->countVolunteers($eventId, $roundId)
        ];
    }

    public function getRescuerCount(?int $eventId = null, ?int $roundId = null): array
    {
        return [
            'count' => $this->rescuerRepository->countRescuers($eventId, $roundId)
        ];
    }

    public function getAccountingCategoryKpi(AccountingType $accountingType, ?int $eventId = null, ?int $roundId = null): array
    {
        $event = $eventId !== null ? $this->eventRepository->find($eventId) : null;
        $round = $roundId !== null ? $this->roundRepository->find($roundId) : null;

        $accountings = $this->accountingRepository->findByEventAndRound($accountingType->value, $event, $round);

        $kpi = [];
        foreach ($accountings as $accounting) {
            $categoryId = (int)$accounting->getAccountingCategory()?->getId();

            $amount = $this->amountHelper->getAccountingAmount($accounting, $round);

            if (!isset($kpi[$categoryId])) {
                $kpi[$categoryId] = [
                    'accountingCategory' => $accounting->getAccountingCategory(),
                    'qty' => 0,
                    'amount' => 0,
                ];
            }
            $kpi[$categoryId]['amount'] += $amount;
            $kpi[$categoryId]['qty'] += $accounting->getQuantity();
        }

        return array_values($kpi);
    }

    public function getAccountingKpi(AccountingType $accountingType, ?int $eventId = null, ?int $roundId = null): array
    {
        $event = $eventId !== null ? $this->eventRepository->find($eventId) : null;
        $round = $roundId !== null ? $this->roundRepository->find($roundId) : null;

        $accountings = $this->accountingRepository->findByEventAndRound($accountingType->value, $event, $round);

        $kpi = [];
        foreach ($accountings as $accounting) {
            $price = $accounting->getUnitPrice() * $accounting->getQuantity();

            if ($round !== null && $accounting->getEvent() !== null && $accounting->getRound() === null) {
                $price /= $accounting->getEvent()->getRounds()->count();
            }

            $kpi[] = [
                'accounting' => $accounting,
                'qty' => $accounting->getQuantity(),
                'amount' => $price,
            ];
        }

        return $kpi;
    }

    public function getIncomesTicketsKpi(?int $eventId = null, ?int $roundId = null): array
    {
        $event = $eventId !== null ? $this->eventRepository->find($eventId) : null;
        $round = $roundId !== null ? $this->roundRepository->find($roundId) : null;

        $tickets = $this->ticketRepository->findByEventAndRound($event, $round);

        $kpi = [];
        $ticketIds = [];
        foreach ($tickets as $ticket) {
            if (!$ticket->getPilotRoundCategories()->isEmpty()) {
                $entity = 'pilot';
            } elseif (!$ticket->getVisitors()->isEmpty()) {
                $entity = 'visitor';
            } else {
                continue;
            }

            if ($ticket->getParentTicket() !== null) {
                $parentTicket = $ticket->getParentTicket();
                if (in_array($parentTicket->getId(), $ticketIds)) {
                    continue;
                }
                $ticketIds[] = $parentTicket->getId();

                $childrenRounds = [];
                foreach ($parentTicket->getTickets() as $childTicket) {
                    foreach ($childTicket->getRounds() as $childRound) {
                        $childrenRounds[$childRound->getId()] = $childRound;
                    }
                }
                $rounds = $round !== null ? [$round] : $childrenRounds;

                $categoryKey = $parentTicket->getCategory()?->getId() ?? 0;

                if (!isset($kpi[$categoryKey][$parentTicket->getTicketLabel()])) {
                    $kpi[$categoryKey][$parentTicket->getTicketLabel()] = [
                        'label' => $parentTicket->getTicketLabel(),
                        'entity' => $entity,
                        'rounds' => $rounds,
                        'category' => $parentTicket->getCategory(),
                        'amount' => 0,
                        'qty' => 0,
                        'isPack' => true
                    ];
                }

                $ticketAmountWithoutFees = $this->amountHelper->getAmountWithoutFees($parentTicket->getAmount());

                $kpi[$categoryKey][$parentTicket->getTicketLabel()]['amount'] += $ticketAmountWithoutFees * count($rounds) / count($childrenRounds);
                $kpi[$categoryKey][$parentTicket->getTicketLabel()]['qty'] += 1;
            } else {
                if (in_array($ticket->getId(), $ticketIds)) {
                    continue;
                }
                $ticketIds[] = $ticket->getId();

                $categoryKey = $ticket->getCategory()?->getId() ?? 0;

                if (!isset($kpi[$categoryKey][$ticket->getTicketLabel()])) {
                    $kpi[$categoryKey][$ticket->getTicketLabel()] = [
                        'label' => $ticket->getTicketLabel(),
                        'entity' => $entity,
                        'rounds' => $ticket->getRounds(),
                        'category' => $ticket->getCategory(),
                        'amount' => 0,
                        'qty' => 0,
                        'isPack' => false
                    ];
                }

                $ticketAmountWithoutFees = $this->amountHelper->getAmountWithoutFees($ticket->getAmount());

                $kpi[$categoryKey][$ticket->getTicketLabel()]['amount'] += $ticketAmountWithoutFees;
                $kpi[$categoryKey][$ticket->getTicketLabel()]['qty'] += 1;
            }
        }

        foreach ($kpi as &$item) {
            $item = array_values($item);
        }

        return array_merge(...array_values($kpi));
    }

    public function getAccountingSummariseKpi(?int $eventId = null, ?int $roundId = null): array
    {
        $event = $eventId !== null ? $this->eventRepository->find($eventId) : null;
        $round = $roundId !== null ? $this->roundRepository->find($roundId) : null;

        // Incomes
        $incomeType = AccountingType::INCOME;
        $incomes = $this->accountingRepository->findByEventAndRound($incomeType->value, $event, $round);
        $totalIncomes = 0;
        foreach ($incomes as $income) {
            $totalIncomes += $this->amountHelper->getAccountingAmount($income, $round);
        }

        $incomeTicketsKpi = $this->getIncomesTicketsKpi($eventId, $roundId);
        foreach ($incomeTicketsKpi as $incomeTicket) {
            $totalIncomes += $incomeTicket['amount'];
        }

        // Expenses
        $expenseType = AccountingType::EXPENSE;
        $expenses = $this->accountingRepository->findByEventAndRound($expenseType->value, $event, $round);
        $totalExpenses = 0;
        foreach ($expenses as $expense) {
            $totalExpenses += $this->amountHelper->getAccountingAmount($expense, $round);
        }

        return [
            'totalIncomes' => $totalIncomes,
            'totalExpenses' => $totalExpenses,
        ];
    }
}