<?php

namespace App\Business;

use App\Dto\CreateMemberDto;
use App\Dto\MemberDto;
use App\Entity\Member;
use App\Repository\PersonRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

readonly class MemberBusiness
{
    public function __construct(
        private PersonRepository       $personRepository,
        private EntityManagerInterface $em
    )
    {}

    public function getMembers(
        int $page,
        int $limit,
        ?string $sort = null,
        ?string $order = null,
        ?string $name = null,
        ?string $email = null,
        ?string $phone = null,
        ?string $roleAsso = null,
        ?string $roleVcup = null
    ): array
    {
        $personIdsTotal = $this->personRepository->findFilteredMemberPersonIdsPaginated($page, $limit, $sort, $order, $name, $email, $phone, $roleAsso, $roleVcup);
        $persons = $this->personRepository->findPersonsByIds($personIdsTotal['items']);

        return [
            'members' => $persons,
            'pagination' => [
                'totalItems' => $personIdsTotal['total'],
                'pageIndex' => $page,
                'itemsPerPage' => $limit
            ]
        ];
    }

    public function createMember(CreateMemberDto $memberDto): Member
    {
        $person = $this->personRepository->find($memberDto->personId);
        if ($memberDto->personId === null || $person === null) {
            throw new Exception('Person not found');
        }

        // get member or create new one
        $member = $person->getMember();
        if ($member === null) {
            $member = new Member();
            $member->setPerson($person);
        }

        $member->setRoleAsso($memberDto->roleAsso)
            ->setRoleVcup($memberDto->roleVcup);

        $this->em->persist($member);
        $this->em->flush();

        return $member;
    }

    public function updatePersonMember(Member $member, MemberDto $memberDto): void
    {
        // update person
        $person = $member->getPerson();

        $person->setFirstName($memberDto->firstName)
            ->setLastName($memberDto->lastName)
            ->setEmail($memberDto->email)
            ->setPhone($memberDto->phone)
            ->setAddress($memberDto->address)
            ->setCity($memberDto->city)
            ->setZipCode($memberDto->zipCode)
            ->setCountry($memberDto->country);

        $this->em->persist($person);

        // update member
        $member->setRoleAsso($memberDto->roleAsso)
            ->setRoleVcup($memberDto->roleVcup);

        $this->em->persist($member);

        $this->em->flush();
    }

    public function deleteMember(Member $member): void
    {
        $this->em->remove($member);
        $this->em->flush();
    }
}