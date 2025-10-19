<?php

namespace App\Business;

use App\Dto\VolunteerRoleDto;
use App\Entity\VolunteerRole;
use App\Repository\VolunteerRoleRepository;
use Doctrine\ORM\EntityManagerInterface;

readonly class VolunteerRoleBusiness
{
    public function __construct(
        private VolunteerRoleRepository $volunteerRoleRepository,
        private EntityManagerInterface $em
    )
    {}

    public function createVolunteerRole(VolunteerRoleDto $volunteerRoleDto): VolunteerRole
    {
        $volunteerRole = $this->volunteerRoleRepository->findBy(['name' => $volunteerRoleDto->name], [], 1)[0] ?? null;

        if ($volunteerRole === null) {
            $volunteerRole = new VolunteerRole();
            $volunteerRole->setName($volunteerRoleDto->name);
            $this->em->persist($volunteerRole);
        }

        $this->em->flush();

        return $volunteerRole;
    }

    public function deleteVolunteerRole(VolunteerRole $volunteerRole): void
    {
        $this->em->remove($volunteerRole);
        $this->em->flush();
    }
}