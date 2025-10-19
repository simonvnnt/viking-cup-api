<?php

namespace App\Controller\Api;

use App\Business\VolunteerRoleBusiness;
use App\Dto\VolunteerRoleDto;
use App\Entity\VolunteerRole;
use App\Repository\VolunteerRoleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/volunteer-roles', name: 'api_volunteer_roles')]
class VolunteerRoleApiController extends AbstractController
{
    #[Route('', name: 'list', methods: ['GET'])]
    public function getVolunteerRoles(
        VolunteerRoleRepository $volunteerRoleRepository
    ): JsonResponse
    {
        $volunteerRoles = $volunteerRoleRepository->findAll();

        return $this->json($volunteerRoles, Response::HTTP_OK, [], ['groups' => ['volunteerRole']]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function createVolunteerRole(
        VolunteerRoleBusiness $volunteerRoleBusiness,
        #[MapRequestPayload] VolunteerRoleDto $volunteerRoleDto
    ): Response
    {
        $volunteerRole = $volunteerRoleBusiness->createVolunteerRole($volunteerRoleDto);

        return $this->json($volunteerRole, Response::HTTP_OK, [], ['groups' => ['volunteerRole']]);
    }

    #[Route('/{volunteerRole}', name: 'delete', methods: ['DELETE'])]
    public function deleteVolunteerRole(
        VolunteerRoleBusiness $volunteerRoleBusiness,
        VolunteerRole $volunteerRole
    ): Response
    {
        $volunteerRoleBusiness->deleteVolunteerRole($volunteerRole);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}