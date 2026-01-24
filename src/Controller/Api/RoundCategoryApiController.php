<?php

namespace App\Controller\Api;

use App\Business\RoundCategoryBusiness;
use App\Dto\RoundCategoryDto;
use App\Entity\Category;
use App\Entity\Event;
use App\Entity\Round;
use App\Entity\RoundCategory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/roundCategories', name: 'api_round_categories')]
class RoundCategoryApiController extends AbstractController
{
    #[Route('/roundCategory/{round}/{category}', name: 'round_category', methods: ['GET'])]
    public function getRoundCategory(
        RoundCategoryBusiness $roundCategoryBusiness,
        Category $category,
        Round $round
    ): Response
    {
        $roundCategory = $roundCategoryBusiness->getRoundCategory($round, $category);

        return $this->json($roundCategory, 200, [], ['groups' => ['roundCategory']]);
    }

    #[Route('/event/{event}/{category}', name: 'get_by_event', methods: ['GET'])]
    #[Route('/round/{round}/{category}', name: 'get_by_round', methods: ['GET'])]
    public function displayTop(
        RoundCategoryBusiness $roundCategoryBusiness,
        Category $category,
        ?Event $event = null,
        ?Round $round = null
    ): Response
    {
        $displayTop = $roundCategoryBusiness->displayTop($category, $round, $event);

        return $this->json(['displayTop' => $displayTop]);
    }

    #[Route('/{roundCategory}', name: 'update', methods: ['PUT'])]
    public function updateRoundCategory(
        RoundCategory $roundCategory,
        #[MapRequestPayload] RoundCategoryDto $roundCategoryDto,
        RoundCategoryBusiness $roundCategoryBusiness
    ): Response
    {
        $roundCategoryBusiness->updateRoundCategory($roundCategory, $roundCategoryDto);

        return new Response('', 204);
    }
}