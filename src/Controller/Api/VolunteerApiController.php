<?php

namespace App\Controller\Api;

use App\Business\VolunteerBusiness;
use App\Dto\CreateVolunteerDto;
use App\Dto\PersonVolunteerDto;
use App\Entity\Person;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/volunteers', name: 'api_volunteers')]
class VolunteerApiController extends AbstractController
{
    #[Route('', name: 'list', methods: ['GET'])]
    public function getVolunteers(
        VolunteerBusiness $volunteerBusiness,
        #[MapQueryParameter] ?int $page,
        #[MapQueryParameter] ?int $limit,
        #[MapQueryParameter] ?string $sort,
        #[MapQueryParameter] ?string $order,
        #[MapQueryParameter] ?int    $eventId = null,
        #[MapQueryParameter] ?int    $roundId = null,
        #[MapQueryParameter] ?string $name = null,
        #[MapQueryParameter] ?string $email = null,
        #[MapQueryParameter] ?string $phone = null,
        #[MapQueryParameter] ?int    $roleId = null
    ): JsonResponse
    {
        $volunteers = $volunteerBusiness->getVolunteers(
            $page ?? 1,
            $limit ?? 20, $sort,
            $order,
            $eventId,
            $roundId,
            $name,
            $email,
            $phone,
            $roleId
        );

        return $this->json($volunteers, Response::HTTP_OK, [], ['groups' => ['volunteer', 'role', 'volunteerRole', 'volunteerRound', 'round', 'roundDetails', 'roundDetail', 'roundEvent', 'event']]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function createVolunteer(
        VolunteerBusiness $volunteerBusiness,
        #[MapRequestPayload] CreateVolunteerDto $volunteerDto
    ): Response
    {
        $volunteerBusiness->createVolunteer($volunteerDto);

        return new Response();
    }

    #[Route('/{person}', name: 'update', methods: ['PUT'])]
    public function updateVolunteer(
        VolunteerBusiness $volunteerBusiness,
        Person $person,
        #[MapRequestPayload] PersonVolunteerDto $volunteerDto
    ): Response
    {
        $volunteerBusiness->updatePersonVolunteer($person, $volunteerDto);

        return new Response();
    }

    #[Route('/{person}', name: 'delete', methods: ['DELETE'])]
    public function deleteVolunteer(
        VolunteerBusiness $volunteerBusiness,
        Person $person
    ): Response
    {
        $volunteerBusiness->deleteVolunteer($person);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}