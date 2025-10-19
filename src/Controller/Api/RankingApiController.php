<?php

namespace App\Controller\Api;

use App\Business\RankingBusiness;
use App\Repository\CategoryRepository;
use App\Repository\EventRepository;
use App\Repository\RoundRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/ranking', name: 'api_qualifying')]
class RankingApiController extends AbstractController
{
    #[Route('/round', name: 'round_ranking', methods: ['GET'])]
    public function getRoundRanking(
        RankingBusiness $rankingBusiness,
        RoundRepository $roundRepository,
        CategoryRepository $categoryRepository,
        #[MapQueryParameter] int $round,
        #[MapQueryParameter] int $category
    ): Response
    {
        $round = $roundRepository->find($round);
        $category = $categoryRepository->find($category);

        $rankingPilotRoundCategory = $rankingBusiness->getRoundRanking($round, $category);

        return $this->json($rankingPilotRoundCategory, 200, [], ['groups' => ['pilot', 'pilotPerson', 'person', 'pilotEvent', 'round', 'category']]);
    }

    #[Route('/event', name: 'event_ranking', methods: ['GET'])]
    public function getEventRanking(
        RankingBusiness $rankingBusiness,
        EventRepository $eventRepository,
        CategoryRepository $categoryRepository,
        #[MapQueryParameter] int $event,
        #[MapQueryParameter] int $category
    ): Response
    {
        $event = $eventRepository->find($event);
        $category = $categoryRepository->find($category);

        $rankingPilotEventCategory = $rankingBusiness->getEventRanking($event, $category);

        return $this->json($rankingPilotEventCategory, 200, [], ['groups' => ['pilot', 'pilotPerson', 'person', 'pilotEvent', 'round', 'category']]);
    }
}