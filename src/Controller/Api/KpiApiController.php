<?php

namespace App\Controller\Api;

use App\Business\KpiBusiness;
use App\Enum\AccountingType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/kpi', name: 'api_kpi')]
class KpiApiController extends AbstractController
{
    #[Route('/pilots/count', methods: ['GET'])]
    public function getPilotsCount(
        KpiBusiness $kpiBusiness,
        #[MapQueryParameter] ?int $eventId = null,
        #[MapQueryParameter] ?int $roundId = null,
    ): JsonResponse
    {
        $pilotsKpi = $kpiBusiness->getPilotsCount($eventId, $roundId);

        return $this->json($pilotsKpi, Response::HTTP_OK, [], ['groups' => ['category']]);
    }

    #[Route('/visitors/count', methods: ['GET'])]
    public function getVisitorsCount(
        KpiBusiness $kpiBusiness,
        #[MapQueryParameter] ?int $eventId = null,
        #[MapQueryParameter] ?int $roundId = null,
    ): JsonResponse
    {
        $visitorsKpi = $kpiBusiness->getVisitorsCount($eventId, $roundId);

        return $this->json($visitorsKpi, Response::HTTP_OK, [], ['groups' => ['roundDetail', 'roundDetailRound', 'round', 'roundEvent', 'event']]);
    }

    #[Route('/medias/count', methods: ['GET'])]
    public function getMediasCount(
        KpiBusiness $kpiBusiness,
        #[MapQueryParameter] ?int $eventId = null,
        #[MapQueryParameter] ?int $roundId = null,
    ): JsonResponse
    {
        $mediasKpi = $kpiBusiness->getMediasCount($eventId, $roundId);

        return $this->json($mediasKpi);
    }

    #[Route('/commissaires/count', methods: ['GET'])]
    public function getCommissairesCount(
        KpiBusiness $kpiBusiness,
        #[MapQueryParameter] ?int $eventId = null,
        #[MapQueryParameter] ?int $roundId = null,
    ): JsonResponse
    {
        $mediasKpi = $kpiBusiness->getCommissairesCount($eventId, $roundId);

        return $this->json($mediasKpi);
    }

    #[Route('/volunteers/count', methods: ['GET'])]
    public function getVolunteersCount(
        KpiBusiness $kpiBusiness,
        #[MapQueryParameter] ?int $eventId = null,
        #[MapQueryParameter] ?int $roundId = null,
    ): JsonResponse
    {
        $volunteersKpi = $kpiBusiness->getVolunteerCount($eventId, $roundId);

        return $this->json($volunteersKpi);
    }

    #[Route('/rescuers/count', methods: ['GET'])]
    public function getRescuersCount(
        KpiBusiness $kpiBusiness,
        #[MapQueryParameter] ?int $eventId = null,
        #[MapQueryParameter] ?int $roundId = null,
    ): JsonResponse
    {
        $rescuersKpi = $kpiBusiness->getRescuerCount($eventId, $roundId);

        return $this->json($rescuersKpi);
    }

    #[Route('/expenses-category', methods: ['GET'])]
    public function getExpensesCategoryKpi(
        KpiBusiness $kpiBusiness,
        #[MapQueryParameter] ?int $eventId = null,
        #[MapQueryParameter] ?int $roundId = null,
    ): JsonResponse
    {
        $expenseType = AccountingType::EXPENSE;
        $expensesKpi = $kpiBusiness->getAccountingCategoryKpi($expenseType, $eventId, $roundId);

        return $this->json($expensesKpi, Response::HTTP_OK, [], ['groups' => ['accounting', 'accountingAccountingCategory', 'accountingCategory', 'accountingRound', 'round', 'accountingEvent', 'event']]);
    }

    #[Route('/incomes-category', methods: ['GET'])]
    public function getIncomesCategoryKpi(
        KpiBusiness $kpiBusiness,
        #[MapQueryParameter] ?int $eventId = null,
        #[MapQueryParameter] ?int $roundId = null,
    ): JsonResponse
    {
        $incomeType = AccountingType::INCOME;
        $incomesKpi = $kpiBusiness->getAccountingCategoryKpi($incomeType, $eventId, $roundId);

        return $this->json($incomesKpi, Response::HTTP_OK, [], ['groups' => ['accounting', 'accountingAccountingCategory', 'accountingCategory', 'accountingRound', 'round', 'accountingEvent', 'event']]);
    }
}