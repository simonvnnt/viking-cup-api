<?php

namespace App\Controller\Api;

use App\Business\BattleBusiness;
use App\Entity\Battle;
use App\Entity\PilotRoundCategory;
use App\Repository\CategoryRepository;
use App\Repository\RoundRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/battles', name: 'api_battles')]
class BattleApiController extends AbstractController
{
    #[Route('/versus', name: 'battle_versus', methods: ['GET'])]
    public function getBattleVersus(
        BattleBusiness $battleBusiness,
        RoundRepository $roundRepository,
        CategoryRepository $categoryRepository,
        #[MapQueryParameter] int $round,
        #[MapQueryParameter] int $category
    ): Response
    {
        $round = $roundRepository->find($round);
        $category = $categoryRepository->find($category);

        $battleVersus = $battleBusiness->getBattleVersus($round, $category);

        return $this->json($battleVersus, 200, [], ['groups' => ['battle', 'battleLeader', 'battleChaser', 'battleWinner', 'pilotRoundCategory', 'pilotRoundCategoryPilot', 'pilot', 'pilotPerson', 'person', 'pilotEvents', 'pilotEvent', 'pilotEventEvent', 'event']]);
    }

    #[Route('/reset', name: 'reset_battle', methods: ['POST'])]
    public function resetBattle(
        BattleBusiness $battleBusiness,
        RoundRepository $roundRepository,
        CategoryRepository $categoryRepository,
        #[MapQueryParameter] int $round,
        #[MapQueryParameter] int $category
    ): Response
    {
        $round = $roundRepository->find($round);
        $category = $categoryRepository->find($category);

        $battleBusiness->resetBattle($round, $category);

        return new Response();
    }

    #[Route('/init', name: 'init_battle_versus', methods: ['POST'])]
    public function initBattleVersus(
        BattleBusiness $battleBusiness,
        RoundRepository $roundRepository,
        CategoryRepository $categoryRepository,
        #[MapQueryParameter] int $round,
        #[MapQueryParameter] int $category
    ): Response
    {
        $round = $roundRepository->find($round);
        $category = $categoryRepository->find($category);

        $battleBusiness->initBattleVersus($round, $category);

        return new Response();
    }

    #[Route('/generate', name: 'generate_battle_versus', methods: ['POST'])]
    public function generateBattleVersus(
        BattleBusiness $battleBusiness,
        RoundRepository $roundRepository,
        CategoryRepository $categoryRepository,
        #[MapQueryParameter] int $round,
        #[MapQueryParameter] int $category,
        #[MapQueryParameter] int $passage
    ): Response
    {
        $round = $roundRepository->find($round);
        $category = $categoryRepository->find($category);

        $battleBusiness->generateNextRound($round, $category, $passage);

        return new Response();
    }

    #[Route('/{battle}/winner/{winner}', name: 'set_battle_winner', methods: ['PUT'])]
    public function setBattleWinner(
        BattleBusiness $battleBusiness,
        RoundRepository $roundRepository,
        CategoryRepository $categoryRepository,
        Battle $battle,
        ?PilotRoundCategory $winner,
        #[MapQueryParameter] int $round,
        #[MapQueryParameter] int $category
    ): Response
    {
        $round = $roundRepository->find($round);
        $category = $categoryRepository->find($category);

        $battleBusiness->setBattleWinner($round, $category, $battle, $winner);

        return new Response();
    }

    #[Route('/ranking', name: 'battle_ranking', methods: ['GET'])]
    public function getBattleRanking(
        BattleBusiness $battleBusiness,
        RoundRepository $roundRepository,
        CategoryRepository $categoryRepository,
        #[MapQueryParameter] int $round,
        #[MapQueryParameter] int $category
    ): Response
    {
        $round = $roundRepository->find($round);
        $category = $categoryRepository->find($category);

        $battleRanking = $battleBusiness->getBattleRanking($round, $category);

        return $this->json($battleRanking, 200, [], ['groups' => ['pilot', 'pilotPerson', 'person', 'pilotEvent', 'round', 'category']]);
    }
}