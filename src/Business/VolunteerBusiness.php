<?php

namespace App\Business;

use App\Dto\CreateVolunteerDto;
use App\Dto\PersonVolunteerDto;
use App\Dto\VolunteerDto;
use App\Entity\Volunteer;
use App\Entity\Person;
use App\Helper\LinkHelper;
use App\Repository\PersonRepository;
use App\Repository\RoundDetailRepository;
use App\Repository\RoundRepository;
use App\Repository\VolunteerRoleRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Serializer\SerializerInterface;

readonly class VolunteerBusiness
{
    public function __construct(
        private PersonRepository       $personRepository,
        private RoundRepository        $roundRepository,
        private RoundDetailRepository  $roundDetailRepository,
        private VolunteerRoleRepository $volunteerRoleRepository,
        private LinkHelper             $linkHelper,
        private SerializerInterface    $serializer,
        private EntityManagerInterface $em
    )
    {}

    public function getVolunteers(
        int $page,
        int $limit,
        ?string $sort = null,
        ?string $order = null,
        ?int    $eventId = null,
        ?int    $roundId = null,
        ?string $name = null,
        ?string $email = null,
        ?string $phone = null,
        ?int    $roleId = null
    ): array
    {
        $personIdsTotal = $this->personRepository->findFilteredVolunteerPersonIdsPaginated($page, $limit, $sort, $order, $eventId, $roundId, $name, $email, $phone, $roleId);
        $persons = $this->personRepository->findPersonsByIds($personIdsTotal['items']);

        $volunteerPersons = [];
        /** @var Person $person */
        foreach ($persons as $person) {
            $personArray = $this->serializer->normalize($person, 'json', ['groups' => ['person', 'personRoundDetails', 'roundDetail', 'personLinks', 'link', 'linkLinkType', 'linkType']]);

            $volunteers = $person->getVolunteers()->filter(function (Volunteer $volunteer) use ($eventId, $roundId, $roleId) {
                return (!$eventId || $volunteer->getRound()->getEvent()->getId() === $eventId) &&
                    (!$roundId || $volunteer->getRound()->getId() === $roundId) &&
                    (!$roleId || $volunteer->getRole()?->getId() === $roleId);
            });

            $personArray['volunteers'] = array_values($volunteers->toArray());

            if (!empty($personArray['volunteers'])) {
                $volunteerPersons[] = $personArray;
            }
        }

        return [
            'pagination' => [
                'totalItems' => $personIdsTotal['total'],
                'pageIndex' => $page,
                'itemsPerPage' => $limit
            ],
            'volunteers' => $volunteerPersons
        ];
    }

    /**
     * Creates new volunteers associated with a person.
     *
     * @param CreateVolunteerDto $volunteerDto
     *
     * @return Person|null
     * @throws Exception
     */
    public function createVolunteer(CreateVolunteerDto $volunteerDto): ?Person
    {
        $person = $this->personRepository->find($volunteerDto->personId);
        if ($volunteerDto->personId === null || $person === null) {
            throw new Exception('Person not found');
        }

        $this->updatePersonRoundDetails($person, $volunteerDto->roundDetails);
        $this->em->persist($person);

        // update volunteers
        $volunteerDto = $this->serializer->denormalize($volunteerDto->volunteers, VolunteerDto::class . '[]');
        $this->updateVolunteers($person, $volunteerDto, false);

        $this->em->flush();

        return $person;
    }

    public function updatePersonVolunteer(Person $person, PersonVolunteerDto $personVolunteerDto): void
    {
        // update person
        $person->setFirstName($personVolunteerDto->firstName)
            ->setLastName($personVolunteerDto->lastName)
            ->setEmail($personVolunteerDto->email)
            ->setPhone($personVolunteerDto->phone)
            ->setWarnings($personVolunteerDto->warnings);

        $this->updatePersonRoundDetails($person, $personVolunteerDto->roundDetails);

        $this->em->persist($person);

        // update instagram link
        if (!empty($personVolunteerDto->instagram)) {
            $this->linkHelper->upsertInstagramLink($person, $personVolunteerDto->instagram);
        }

        // update volunteers
        $volunteerDtos = $this->serializer->denormalize($personVolunteerDto->volunteers, VolunteerDto::class . '[]');
        $this->updateVolunteers($person, $volunteerDtos);

        $this->em->flush();
    }

    public function updateVolunteers(Person $person, array $volunteerDtos, bool $cleanExistent = true): void
    {
        $volunteers = $person->getVolunteers();

        if ($cleanExistent) {
            // delete volunteers that are not in the DTO
            $this->deleteVolunteers($volunteers, $volunteerDtos);
        }

        /** @var VolunteerDto $volunteerDto */
        foreach ($volunteerDtos as $volunteerDto) {
            if ($volunteerDto->id) {
                $volunteer = $volunteers->filter(fn(Volunteer $s) => $s->getId() === $volunteerDto->id)->first();
                if ($volunteer === false) {
                    continue;
                }
            } else {
                $volunteer = new Volunteer();
                $volunteer->setPerson($person);
            }

            $round = $this->roundRepository->find($volunteerDto->roundId);
            if ($round === null) {
                continue;
            }

            $volunteer->setRound($round);

            if (!empty($volunteerDto->roleId)) {
                $role = $this->volunteerRoleRepository->find($volunteerDto->roleId);
                $volunteer->setRole($role ?? null);
            }

            $this->em->persist($volunteer);
        }
    }

    private function updatePersonRoundDetails(Person $person, array $roundDetails): void
    {
        // Supprimer les détails de rounds qui ne sont plus dans la liste de présence
        foreach ($person->getRoundDetails()->toArray() as $roundDetail) {
            if (!in_array($roundDetail->getId(), $roundDetails)) {
                $person->removeRoundDetail($roundDetail);
            }
        }

        // Ajouter les nouveaux détails de rounds
        foreach ($roundDetails as $roundDetailId) {
            // Vérifier si le détail de round existe déjà
            if ($person->getRoundDetails()->exists(fn($key, $rd) => $rd->getId() === $roundDetailId)) {
                continue;
            }

            $roundDetail = $this->roundDetailRepository->find($roundDetailId);
            if ($roundDetail !== null) {
                $person->addRoundDetail($roundDetail);

                if (!$person->getRounds()->contains($roundDetail->getRound())) {
                    $person->addRound($roundDetail->getRound());
                }
            }
        }
    }

    /**
     * Deletes volunteers from the database.
     *
     * @param Collection<int, Volunteer> $volunteers
     * @param VolunteerDto[] $volunteerDtos
     */
    private function deleteVolunteers(Collection $volunteers, array $volunteerDtos): void
    {
        $volunteerDtoIds = array_map(fn(VolunteerDto $dto) => $dto->id, $volunteerDtos);
        $volunteersToDelete = $volunteers->filter(fn(Volunteer $v) => !in_array($v->getId(), $volunteerDtoIds));

        foreach ($volunteersToDelete as $volunteer) {
            $this->em->remove($volunteer);
        }
    }

    public function deleteVolunteer(Person $person): void
    {
        foreach ($person->getVolunteers()->toArray() as $volunteer) {
            $this->em->remove($volunteer);
        }

        $this->em->flush();
    }
}