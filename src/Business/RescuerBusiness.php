<?php

namespace App\Business;

use App\Dto\CreateRescuerDto;
use App\Dto\PersonRescuerDto;
use App\Dto\RescuerDto;
use App\Entity\Rescuer;
use App\Entity\Person;
use App\Repository\PersonRepository;
use App\Repository\RoundRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Serializer\SerializerInterface;

readonly class RescuerBusiness
{
    public function __construct(
        private PersonRepository       $personRepository,
        private RoundRepository        $roundRepository,
        private SerializerInterface    $serializer,
        private EntityManagerInterface $em
    )
    {}

    public function getRescuers(
        int $page,
        int $limit,
        ?string $sort = null,
        ?string $order = null,
        ?int    $eventId = null,
        ?int    $roundId = null,
        ?string $name = null,
        ?string $email = null,
        ?string $phone = null,
        ?string $role = null
    ): array
    {
        $personIdsTotal = $this->personRepository->findFilteredRescuerPersonIdsPaginated($page, $limit, $sort, $order, $eventId, $roundId, $name, $email, $phone, $role);
        $persons = $this->personRepository->findPersonsByIds($personIdsTotal['items']);

        $rescuerPersons = [];
        /** @var Person $person */
        foreach ($persons as $person) {
            $personArray = $this->serializer->normalize($person, 'json', ['groups' => ['person', 'personRoundDetails', 'roundDetail', 'personLinks', 'link', 'linkLinkType', 'linkType']]);

            $rescuers = $person->getRescuers()->filter(function (Rescuer $rescuer) use ($eventId, $roundId, $role) {
                return (!$eventId || $rescuer->getRound()->getEvent()->getId() === $eventId) &&
                    (!$roundId || $rescuer->getRound()->getId() === $roundId) &&
                    (!$role || str_contains($rescuer->getRole(), $role) !== false);
            });

            $personArray['rescuers'] = array_values($rescuers->toArray());

            if (!empty($personArray['rescuers'])) {
                $rescuerPersons[] = $personArray;
            }
        }

        return [
            'pagination' => [
                'totalItems' => $personIdsTotal['total'],
                'pageIndex' => $page,
                'itemsPerPage' => $limit
            ],
            'rescuers' => $rescuerPersons
        ];
    }

    /**
     * Creates new rescuers associated with a person.
     *
     * @param CreateRescuerDto $rescuerDto
     *
     * @return Person|null
     * @throws Exception
     */
    public function createRescuer(CreateRescuerDto $rescuerDto): ?Person
    {
        $person = $this->personRepository->find($rescuerDto->personId);
        if ($rescuerDto->personId === null || $person === null) {
            throw new Exception('Person not found');
        }

        // update rescuers
        $rescuersDto = $this->serializer->denormalize($rescuerDto->rescuers, RescuerDto::class . '[]');
        $this->updateRescuers($person, $rescuersDto, false);

        $this->em->flush();

        return $person;
    }

    public function updatePersonRescuer(Person $person, PersonRescuerDto $personRescuerDto): void
    {
        // update person
        $person->setFirstName($personRescuerDto->firstName)
            ->setLastName($personRescuerDto->lastName)
            ->setEmail($personRescuerDto->email)
            ->setPhone($personRescuerDto->phone)
            ->setWarnings($personRescuerDto->warnings);

        $this->em->persist($person);

        // update rescuers
        $rescuerDtos = $this->serializer->denormalize($personRescuerDto->rescuers, RescuerDto::class . '[]');
        $this->updateRescuers($person, $rescuerDtos);

        $this->em->flush();
    }

    public function updateRescuers(Person $person, array $rescuerDtos, bool $cleanExistent = true): void
    {
        $rescuers = $person->getRescuers();

        if ($cleanExistent) {
            // delete rescuers that are not in the DTO
            $this->deleteRescuers($rescuers, $rescuerDtos);
        }

        /** @var RescuerDto $rescuerDto */
        foreach ($rescuerDtos as $rescuerDto) {
            if ($rescuerDto->id) {
                $rescuer = $rescuers->filter(fn(Rescuer $s) => $s->getId() === $rescuerDto->id)->first();
                if ($rescuer === false) {
                    continue;
                }
            } else {
                $rescuer = new Rescuer();
                $rescuer->setPerson($person);
            }

            $round = $this->roundRepository->find($rescuerDto->roundId);
            if ($round === null) {
                continue;
            }

            $rescuer->setRound($round)
                ->setRole($rescuerDto->role);

            $this->em->persist($rescuer);
        }
    }

    /**
     * Deletes rescuers from the database.
     *
     * @param Collection<int, Rescuer> $rescuers
     * @param RescuerDto[] $rescuerDtos
     */
    private function deleteRescuers(Collection $rescuers, array $rescuerDtos): void
    {
        $rescuerDtoIds = array_map(fn(RescuerDto $dto) => $dto->id, $rescuerDtos);
        $rescuersToDelete = $rescuers->filter(fn(Rescuer $s) => !in_array($s->getId(), $rescuerDtoIds));

        foreach ($rescuersToDelete as $rescuer) {
            $this->em->remove($rescuer);
        }
    }

    public function deletePersonRescuer(Person $person): void
    {
        foreach ($person->getRescuers()->toArray() as $rescuer) {
            $this->em->remove($rescuer);
        }

        $this->em->flush();
    }
}