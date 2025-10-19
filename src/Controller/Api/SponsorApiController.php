<?php

namespace App\Controller\Api;

use App\Business\SponsorBusiness;
use App\Dto\CreateSponsorDto;
use App\Dto\SponsorDto;
use App\Entity\Sponsor;
use App\Entity\Sponsorship;
use App\Repository\SponsorRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Attribute\MapUploadedFile;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/sponsors', name: 'api_sponsors')]
class SponsorApiController extends AbstractController
{
    #[Route('/public', name: 'list_website', methods: ['GET'])]
    public function list(SponsorRepository $sponsorRepository): JsonResponse
    {
        $sponsors = $sponsorRepository->findBy(['displayWebsite' => true]);

        return $this->json($sponsors, Response::HTTP_OK, [], ['groups' => 'sponsor:read']);
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function getVisitors(
        SponsorBusiness $sponsorBusiness,
        #[MapQueryParameter] ?int $page,
        #[MapQueryParameter] ?int $limit,
        #[MapQueryParameter] ?string $sort,
        #[MapQueryParameter] ?string $order,
        #[MapQueryParameter] ?string $name = null,
        #[MapQueryParameter] ?string $contact = null,
        #[MapQueryParameter] ?int    $eventId = null,
        #[MapQueryParameter] ?int    $roundId = null,
        #[MapQueryParameter] ?string $status = null,
        #[MapQueryParameter] ?string $counterpartType = null,
        #[MapQueryParameter] ?int    $minAmount = null,
        #[MapQueryParameter] ?int    $maxAmount = null,
        #[MapQueryParameter] ?string $otherCounterpart = null,
        #[MapQueryParameter] ?bool   $hasContract = null
    ): JsonResponse
    {
        $sponsors = $sponsorBusiness->getSponsors(
            $page ?? 1,
            $limit ?? 20, $sort,
            $order,
            $name,
            $contact,
            $eventId,
            $roundId,
            $status,
            $counterpartType,
            $minAmount,
            $maxAmount,
            $otherCounterpart,
            $hasContract
        );

        return $this->json($sponsors, Response::HTTP_OK, [], ['groups' => ['sponsorship', 'sponsorshipCounterparts', 'sponsorshipCounterpart', 'sponsorshipEvent', 'event', 'sponsorshipRound', 'round', 'roundEvent', 'sponsorshipSponsor', 'sponsor', 'sponsorLinks', 'link', 'linkLinkType', 'linkType']]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function createSponsor(
        SponsorBusiness $sponsorBusiness,
        Request $request,
        SerializerInterface $serializer,
        #[MapUploadedFile] UploadedFile|array $sponsorImage,
        #[MapUploadedFile(name: 'contractFiles')] array $contractFiles = []
    ): Response
    {
        $sponsorDto = $request->request->get('sponsor');
        $sponsorDto = $serializer->deserialize($sponsorDto, CreateSponsorDto::class, 'json');

        $sponsor = $sponsorBusiness->createSponsor($sponsorDto, !empty($sponsorImage) ? $sponsorImage : null, $contractFiles);

        return $this->json($sponsor, Response::HTTP_CREATED, [], ['groups' => ['sponsor', 'sponsorLinks', 'link', 'linkLinkType', 'linkType']]);
    }

    #[Route('/{sponsor}', name: 'update', methods: ['POST'])]
    public function updateSponsor(
        SponsorBusiness $sponsorBusiness,
        Request $request,
        SerializerInterface $serializer,
        Sponsor $sponsor,
        #[MapUploadedFile] UploadedFile|array $sponsorImage,
        #[MapUploadedFile(name: 'contractFiles')] array $contractFiles = []
    ): Response
    {
        $sponsorDto = $request->request->get('sponsor');
        $sponsorDto = $serializer->deserialize($sponsorDto, SponsorDto::class, 'json');

        $sponsorBusiness->updatePersonSponsor($sponsor, $sponsorDto, !empty($sponsorImage) ? $sponsorImage : null, $contractFiles);

        return new Response();
    }

    #[Route('/{sponsor}', name: 'delete', methods: ['DELETE'])]
    public function deleteMedia(
        SponsorBusiness $sponsorBusiness,
        Sponsor $sponsor
    ): Response
    {
        $sponsorBusiness->deleteSponsor($sponsor);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/image/{sponsor}', name: 'delete_image', methods: ['DELETE'])]
    public function deleteSponsorImage(
        SponsorBusiness $sponsorBusiness,
        Sponsor $sponsor
    ): Response
    {
        $sponsorBusiness->deleteSponsorImage($sponsor);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/contract/{sponsorship}', name: 'delete_contract', methods: ['DELETE'])]
    public function deleteSponsorshipContract(
        SponsorBusiness $sponsorBusiness,
        Sponsorship $sponsorship
    ): Response
    {
        $sponsorBusiness->deleteSponsorshipContract($sponsorship);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}