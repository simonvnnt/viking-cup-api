<?php

namespace App\Controller\Api;

use App\Business\PilotBusiness;
use App\Dto\CreatePilotDto;
use App\Dto\PilotDto;
use App\Entity\Pilot;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/pilots', name: 'api_pilots')]
class PilotApiController extends AbstractController
{
    #[Route('', name: 'list', methods: ['GET'])]
    public function getPilots(
        PilotBusiness $pilotBusiness,
        #[MapQueryParameter] ?int $page,
        #[MapQueryParameter] ?int $limit,
        #[MapQueryParameter] ?string $sort,
        #[MapQueryParameter] ?string $order,
        #[MapQueryParameter] ?string $name = null,
        #[MapQueryParameter] ?string $email = null,
        #[MapQueryParameter] ?string $phone = null,
        #[MapQueryParameter] ?int    $eventId = null,
        #[MapQueryParameter] ?int    $roundId = null,
        #[MapQueryParameter] ?int    $categoryId = null,
        #[MapQueryParameter] ?string $number = null,
        #[MapQueryParameter] ?bool   $ffsaLicensee = null,
        #[MapQueryParameter] ?string $ffsaNumber = null,
        #[MapQueryParameter] ?string $nationality = null,
        #[MapQueryParameter] ?bool   $receivedWindscreenBand = null,
    ): JsonResponse
    {
        $pilots = $pilotBusiness->getPilots(
            $page ?? 1,
            $limit ?? 20, $sort,
            $order,
            $name,
            $email,
            $phone,
            $eventId,
            $roundId,
            $categoryId,
            $number,
            $ffsaLicensee,
            $ffsaNumber,
            $nationality,
            $receivedWindscreenBand
        );

        return $this->json($pilots, Response::HTTP_OK, [], ['groups' => ['pilot', 'pilotPilotRoundCategories', 'pilotRoundCategory', 'pilotRoundCategoryRound', 'pilotRoundCategoryCategory', 'category', 'personPilot', 'person', 'personRounds', 'round', 'roundEvent', 'event', 'pilotEvents', 'pilotEvent', 'pilotEventEvent', 'personRoundDetails', 'roundDetail', 'personLinks', 'link', 'linkLinkType', 'linkType']]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function createPilot(
        PilotBusiness $pilotBusiness,
        #[MapRequestPayload] CreatePilotDto $pilotDto
    ): Response
    {
        $pilotBusiness->createPilot($pilotDto);

        return new Response();
    }

    #[Route('/{pilot}', name: 'update', methods: ['PUT'])]
    public function updatePilot(
        PilotBusiness $pilotBusiness,
        Pilot $pilot,
        #[MapRequestPayload] PilotDto $pilotDto
    ): Response
    {
        $pilotBusiness->updatePersonPilot($pilot, $pilotDto);

        return new Response();
    }

    #[Route('/{pilot}', name: 'delete', methods: ['DELETE'])]
    public function deletePilot(
        PilotBusiness $pilotBusiness,
        Pilot $pilot
    ): Response
    {
        $pilotBusiness->deletePilot($pilot);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}