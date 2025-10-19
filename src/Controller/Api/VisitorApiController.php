<?php

namespace App\Controller\Api;

use App\Business\VisitorBusiness;
use App\Dto\CreateVisitorDto;
use App\Entity\Visitor;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/visitors', name: 'api_visitors')]
class VisitorApiController extends AbstractController
{
    #[Route('', name: 'list', methods: ['GET'])]
    public function getVisitors(
        VisitorBusiness $visitorBusiness,
        #[MapQueryParameter] ?int $page,
        #[MapQueryParameter] ?int $limit,
        #[MapQueryParameter] ?string $sort,
        #[MapQueryParameter] ?string $order,
        #[MapQueryParameter] ?int    $eventId = null,
        #[MapQueryParameter] ?int    $roundId = null,
        #[MapQueryParameter] ?int    $roundDetailId = null,
        #[MapQueryParameter] ?string $name = null,
        #[MapQueryParameter] ?string $email = null,
        #[MapQueryParameter] ?string $phone = null,
        #[MapQueryParameter] ?int    $fromCompanions = null,
        #[MapQueryParameter] ?int    $toCompanions = null,
        #[MapQueryParameter] ?string $fromDate = null,
        #[MapQueryParameter] ?string $toDate = null
    ): JsonResponse
    {
        $visitors = $visitorBusiness->getVisitors(
            $page ?? 1,
            $limit ?? 20, $sort,
            $order,
            $eventId,
            $roundId,
            $roundDetailId,
            $name,
            $email,
            $phone,
            $fromCompanions,
            $toCompanions,
            $fromDate,
            $toDate
        );

        return $this->json($visitors, Response::HTTP_OK, [], ['groups' => ['visitor', 'visitorRoundDetail', 'roundDetail', 'roundDetailRound', 'round', 'roundEvent', 'event', 'visitorPerson', 'person', 'personRoundDetails', 'roundDetail', 'personLinks', 'link', 'linkLinkType', 'linkType']]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function createVisitor(
        VisitorBusiness $visitorBusiness,
        #[MapRequestPayload] CreateVisitorDto $visitorDto
    ): Response
    {
        $visitorBusiness->createVisitor($visitorDto);

        return new Response();
    }

    #[Route('/{visitor}', name: 'delete', methods: ['DELETE'])]
    public function deleteVisitor(
        VisitorBusiness $visitorBusiness,
        Visitor $visitor
    ): Response
    {
        $visitorBusiness->deleteVisitor($visitor);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}