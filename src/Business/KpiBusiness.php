<?php

namespace App\Business;

use App\Enum\AccountingType;
use App\Repository\AccountingRepository;
use App\Repository\CategoryRepository;
use App\Repository\CommissaireRepository;
use App\Repository\EventRepository;
use App\Repository\MediaRepository;
use App\Repository\PilotRepository;
use App\Repository\RescuerRepository;
use App\Repository\RoundDetailRepository;
use App\Repository\RoundRepository;
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
        private EventRepository $eventRepository,
        private RoundRepository $roundRepository,
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

            $price = $accounting->getUnitPrice() * $accounting->getQuantity();

            if ($round !== null && $accounting->getEvent() !== null && $accounting->getRound() === null) {
                $price /= $accounting->getEvent()->getRounds()->count();
            }

            if (!isset($kpi[$categoryId])) {
                $kpi[$categoryId] = [
                    'accountingCategory' => $accounting->getAccountingCategory(),
                    'price' => 0,
                ];
            }
            $kpi[$categoryId]['price'] += $price;
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
                'price' => $price,
            ];
        }

        return $kpi;
    }
}