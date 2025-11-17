<?php

namespace App\Controller\Api;

use App\Business\RoundBusiness;
use App\Entity\Event;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/rounds', name: 'api_rounds')]
class RoundApiController extends AbstractController
{
    #[Route('/{event}', name: 'list', methods: ['GET'])]
    #[Route('', name: 'list', methods: ['GET'])]
    public function getRounds(
        RoundBusiness $roundBusiness,
        Event $event = null
    ): Response
    {
        $rounds = $roundBusiness->getRounds($event);

        return $this->json($rounds, 200, [], ['groups' => ['round', 'roundEvent', 'event', 'eventCategories', 'category', 'roundDetails', 'roundDetail']]);
    }
}