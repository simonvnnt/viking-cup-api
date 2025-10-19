<?php

namespace App\Business;

use App\Dto\CommissaireDto;
use App\Dto\PersonCommissaireDto;
use App\Dto\CreateCommissaireDto;
use App\Entity\Commissaire;
use App\Entity\Person;
use App\Repository\CommissaireTypeRepository;
use App\Repository\PersonRepository;
use App\Repository\RoundRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Serializer\SerializerInterface;

readonly class CommissaireBusiness
{
    public function __construct(
        private PersonRepository       $personRepository,
        private RoundRepository        $roundRepository,
        private CommissaireTypeRepository $commissaireTypeRepository,
        private SerializerInterface    $serializer,
        private EntityManagerInterface $em
    )
    {}

    public function getCommissaires(
        int $page,
        int $limit,
        ?string $sort = null,
        ?string $order = null,
        ?int    $eventId = null,
        ?int    $roundId = null,
        ?string $name = null,
        ?string $email = null,
        ?string $phone = null,
        ?string $licenceNumber = null,
        ?string $asaCode = null,
        ?int    $typeId = null,
        ?bool   $isFlag = null
    ): array
    {
        $personIdsTotal = $this->personRepository->findFilteredCommissairePersonIdsPaginated($page, $limit, $sort, $order, $eventId, $roundId, $name, $email, $phone, $licenceNumber, $asaCode, $typeId, $isFlag);
        $persons = $this->personRepository->findPersonsByIds($personIdsTotal['items']);

        $commissairePersons = [];
        /** @var Person $person */
        foreach ($persons as $person) {
            $personArray = $this->serializer->normalize($person, 'json', ['groups' => ['person', 'personRoundDetails', 'roundDetail']]);

            $commissaires = $person->getCommissaires()->filter(function (Commissaire $commissaire) use ($eventId, $roundId, $licenceNumber, $asaCode, $typeId, $isFlag) {
                return (!$eventId || $commissaire->getRound()->getEvent()->getId() === $eventId) &&
                    (!$roundId || $commissaire->getRound()->getId() === $roundId) &&
                    (!$licenceNumber || str_contains($commissaire->getLicenceNumber(), $licenceNumber) !== false) &&
                    (!$asaCode || str_contains($commissaire->getAsaCode(), $asaCode) !== false) &&
                    (!$typeId || $commissaire->getType()?->getId() === $typeId) &&
                    ($isFlag === null || $commissaire->isFlag() === $isFlag);
            });

            $personArray['commissaires'] = array_values($commissaires->toArray());

            if (!empty($personArray['commissaires'])) {
                $commissairePersons[] = $personArray;
            }
        }

        return [
            'pagination' => [
                'totalItems' => $personIdsTotal['total'],
                'pageIndex' => $page,
                'itemsPerPage' => $limit
            ],
            'commissaires' => $commissairePersons
        ];
    }

    /**
     * Creates a new commissaire.
     *
     * @param CreateCommissaireDto $commissaireDto The data transfer object containing the commissaire information.
     *
     * @return Person|null The created commissaires person.
     * @throws Exception
     */
    public function createCommissaire(CreateCommissaireDto $commissaireDto): ?Person
    {
        $person = $this->personRepository->find($commissaireDto->personId);
        if ($commissaireDto->personId === null || $person === null) {
            throw new Exception('Person not found');
        }

        // update commissaires
        $commissairesDto = $this->serializer->denormalize($commissaireDto->commissaires, CommissaireDto::class . '[]');
        $this->updateCommissaires($person, $commissairesDto);

        $this->em->flush();

        return $person;
    }

    public function updatePersonCommissaire(Person $person, PersonCommissaireDto $personCommissaireDto): void
    {
        // update person
        $person->setFirstName($personCommissaireDto->firstName)
            ->setLastName($personCommissaireDto->lastName)
            ->setEmail($personCommissaireDto->email)
            ->setPhone($personCommissaireDto->phone)
            ->setWarnings($personCommissaireDto->warnings);

        $this->em->persist($person);

        // update commissaires
        $commissairesDto = $this->serializer->denormalize($personCommissaireDto->commissaires, CommissaireDto::class . '[]');
        $this->updateCommissaires($person, $commissairesDto);

        $this->em->flush();
    }

    public function updateCommissaires(Person $person, array $commissaireDtos): void
    {
        $commissaires = $person->getCommissaires();

        // delete commissaires that are not in the DTO
        $this->deleteCommissaires($commissaires, $commissaireDtos);

        /** @var CommissaireDto $commissaireDto */
        foreach ($commissaireDtos as $commissaireDto) {
            if ($commissaireDto->id) {
                $commissaire = $commissaires->filter(fn(Commissaire $s) => $s->getId() === $commissaireDto->id)->first();
                if ($commissaire === false) {
                    continue;
                }
            } else {
                $commissaire = new Commissaire();
                $commissaire->setPerson($person);
            }

            $round = $this->roundRepository->find($commissaireDto->roundId);
            if ($round === null) {
                continue;
            }

            if (!empty($personCommissaireDto->typeId)) {
                $commissaireType = $this->commissaireTypeRepository->find($personCommissaireDto->typeId);
            }

            $commissaire->setRound($round)
                ->setType($commissaireType ?? null)
                ->setLicenceNumber($commissaireDto->licenceNumber)
                ->setAsaCode($commissaireDto->asaCode)
                ->setIsFlag($commissaireDto->isFlag);

            $this->em->persist($commissaire);
        }
    }

    /**
     * Deletes commissaires from the database.
     *
     * @param Collection<int, Commissaire> $commissaires
     * @param CommissaireDto[] $commissaireDtos
     */
    private function deleteCommissaires(Collection $commissaires, array $commissaireDtos): void
    {
        $commissaireDtoIds = array_map(fn(CommissaireDto $dto) => $dto->id, $commissaireDtos);
        $commissairesToDelete = $commissaires->filter(fn(Commissaire $s) => !in_array($s->getId(), $commissaireDtoIds));

        foreach ($commissairesToDelete as $commissaire) {
            $this->em->remove($commissaire);
        }
    }

    public function deletePersonCommissaires(Person $person): void
    {
        foreach ($person->getCommissaires()->toArray() as $commissaire) {
            $this->em->remove($commissaire);
        }

        $this->em->flush();
    }
}