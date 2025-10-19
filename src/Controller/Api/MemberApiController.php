<?php

namespace App\Controller\Api;

use App\Business\MemberBusiness;
use App\Dto\CreateMemberDto;
use App\Dto\MemberDto;
use App\Entity\Member;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/members', name: 'api_members')]
class MemberApiController extends AbstractController
{
    #[Route('', name: 'list', methods: ['GET'])]
    public function getMembers(
        MemberBusiness $memberBusiness,
        #[MapQueryParameter] ?int $page,
        #[MapQueryParameter] ?int $limit,
        #[MapQueryParameter] ?string $sort,
        #[MapQueryParameter] ?string $order,
        #[MapQueryParameter] ?string $name = null,
        #[MapQueryParameter] ?string $email = null,
        #[MapQueryParameter] ?string $phone = null,
        #[MapQueryParameter] ?string $roleAsso = null,
        #[MapQueryParameter] ?string $roleVcup = null,
    ): JsonResponse
    {
        $members = $memberBusiness->getMembers(
            $page ?? 1,
            $limit ?? 20, $sort,
            $order,
            $name,
            $email,
            $phone,
            $roleAsso,
            $roleVcup
        );

        return $this->json($members, Response::HTTP_OK, [], ['groups' => ['member', 'personMember', 'person', 'personRoundDetails', 'roundDetail', 'personLinks', 'link', 'linkLinkType', 'linkType']]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function createMember(
        MemberBusiness $memberBusiness,
        #[MapRequestPayload] CreateMemberDto $createMemberDto
    ): Response
    {
        $memberBusiness->createMember($createMemberDto);

        return new Response();
    }

    #[Route('/{member}', name: 'update', methods: ['PUT'])]
    public function updateMember(
        MemberBusiness $memberBusiness,
        Member $member,
        #[MapRequestPayload] MemberDto $memberDto
    ): Response
    {
        $memberBusiness->updatePersonMember($member, $memberDto);

        return new Response();
    }

    #[Route('/{member}', name: 'delete', methods: ['DELETE'])]
    public function deleteMember(
        MemberBusiness $memberBusiness,
        Member $member
    ): Response
    {
        $memberBusiness->deleteMember($member);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}