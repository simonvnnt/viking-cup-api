<?php

namespace App\Controller\Api;

use App\Business\CommissaireTypeBusiness;
use App\Dto\CommissaireTypeDto;
use App\Entity\CommissaireType;
use App\Repository\CommissaireTypeRepository;
use App\Repository\LinkTypeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/link-types', name: 'api_link_types')]
class LinkTypeApiController extends AbstractController
{
    #[Route('', name: 'list', methods: ['GET'])]
    public function getLinkTypes(
        LinkTypeRepository $linkTypeRepository
    ): JsonResponse
    {
        $linkTypes = $linkTypeRepository->findAll();

        return $this->json($linkTypes, Response::HTTP_OK, [], ['groups' => ['linkType']]);
    }
}