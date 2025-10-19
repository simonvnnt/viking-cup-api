<?php

namespace App\Controller\Api;

use App\Business\PersonBusiness;
use App\Dto\PersonDto;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/persons', name: 'api_persons')]
class PersonApiController extends AbstractController
{
    #[Route('', name: 'list', methods: ['GET'])]
    public function getPersons(
        PersonBusiness $personBusiness,
        #[MapQueryParameter] ?int $page,
        #[MapQueryParameter] ?int $limit,
        #[MapQueryParameter] ?string $sort,
        #[MapQueryParameter] ?string $order,
        #[MapQueryParameter] ?string $person = null
    ): JsonResponse
    {
        $persons = $personBusiness->getPersons(
            $page ?? 1,
            $limit ?? 50,
            $sort,
            $order,
            $person,
        );

        return $this->json($persons, Response::HTTP_OK, [], ['groups' => ['person', 'personRoundDetails', 'roundDetail']]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function createPerson(
        PersonBusiness $personBusiness,
        #[MapRequestPayload] PersonDto $personDto
    ): Response
    {
        $person = $personBusiness->createPerson($personDto);

        return $this->json($person, Response::HTTP_CREATED, [], ['groups' => ['person']]);
    }
}