<?php

namespace App\Controller\Api;

use App\Business\CommissaireTypeBusiness;
use App\Dto\CommissaireTypeDto;
use App\Entity\CommissaireType;
use App\Repository\CommissaireTypeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/commissaire-types', name: 'api_commissaire_types')]
class CommissaireTypeApiController extends AbstractController
{
    #[Route('', name: 'list', methods: ['GET'])]
    public function getCommissaireTypes(
        CommissaireTypeRepository $commissaireTypeRepository
    ): JsonResponse
    {
        $commissaireTypes = $commissaireTypeRepository->findAll();

        return $this->json($commissaireTypes, Response::HTTP_OK, [], ['groups' => ['commissaireType']]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function createCommissaireType(
        CommissaireTypeBusiness $commissaireTypeBusiness,
        #[MapRequestPayload] CommissaireTypeDto $commissaireTypeDto
    ): Response
    {
        $commissaireType = $commissaireTypeBusiness->createCommissaireType($commissaireTypeDto);

        return $this->json($commissaireType, Response::HTTP_OK, [], ['groups' => ['commissaireType']]);
    }

    #[Route('/{commissaireType}', name: 'delete', methods: ['DELETE'])]
    public function deleteCommissaireType(
        CommissaireTypeBusiness $commissaireTypeBusiness,
        CommissaireType $commissaireType
    ): Response
    {
        $commissaireTypeBusiness->deleteCommissaireType($commissaireType);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}