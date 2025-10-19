<?php

namespace App\Controller\Api;

use App\Business\QualifyingBusiness;
use App\Dto\QualifDto;
use App\Entity\Qualifying;
use App\Repository\CategoryRepository;
use App\Repository\PilotRoundCategoryRepository;
use App\Repository\RoundRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/qualifying', name: 'api_qualifying')]
class QualifyingApiController extends AbstractController
{
    #[Route('', name: 'qualifying', methods: ['GET'])]
    public function getPilotRoundCategoryQualifying(
        QualifyingBusiness $qualifyingDetailBusiness,
        PilotRoundCategoryRepository $pilotRoundCategoryRepository,
        #[MapQueryParameter] int $pilotRoundCategory,
    ): Response
    {
        $pilotRoundCategory = $pilotRoundCategoryRepository->find($pilotRoundCategory);

        $roundCategoryPilotsQualifying = $qualifyingDetailBusiness->getPilotRoundCategoryPilotQualifying($pilotRoundCategory);

        return $this->json($roundCategoryPilotsQualifying, Response::HTTP_OK, [], ['groups' => ['qualifying', 'qualifyingDetails', 'qualifyingDetail', 'qualifyingDetailCriteria', 'qualifyingCriteria', 'pilotRoundCategory', 'pilotRoundCategoryPilot', 'pilot', 'pilotPerson', 'person', 'pilotEvents', 'pilotEvent']]);
    }

    #[Route('/ranking', name: 'qualifying_ranking', methods: ['GET'])]
    public function getQualifyingRanking(
        QualifyingBusiness $qualifyingBusiness,
        RoundRepository $roundRepository,
        CategoryRepository $categoryRepository,
        #[MapQueryParameter] int $round,
        #[MapQueryParameter] int $category
    ): Response
    {
        $round = $roundRepository->find($round);
        $category = $categoryRepository->find($category);

        $qualifyingRanking = $qualifyingBusiness->getQualifyingRanking($round, $category);

        return $this->json($qualifyingRanking, 200, [], ['groups' => ['pilot', 'pilotPerson', 'person', 'pilotEvent', 'round', 'category', 'qualifying']]);
    }

    #[Route('/{qualifying}', name: 'update_qualifying', methods: ['PUT'])]
    public function updateQualifying(
        QualifyingBusiness $qualifyingBusiness,
        Qualifying $qualifying,
        #[MapRequestPayload] QualifDto $qualifDto
    ): Response
    {
        $qualifyingBusiness->updateQualifying($qualifying, $qualifDto);

        return new Response();
    }
}