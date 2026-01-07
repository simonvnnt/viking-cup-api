<?php

namespace App\Business;

use App\Dto\CreatePilotDto;
use App\Dto\PilotDto;
use App\Dto\PilotPresenceDto;
use App\Entity\Pilot;
use App\Entity\Person;
use App\Entity\PilotRoundCategory;
use App\Entity\Qualifying;
use App\Helper\LinkHelper;
use App\Helper\PilotHelper;
use App\Repository\CategoryRepository;
use App\Repository\EventRepository;
use App\Repository\PersonRepository;
use App\Repository\RoundRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Serializer\SerializerInterface;

readonly class PilotBusiness
{
    public function __construct(
        private PersonRepository       $personRepository,
        private EventRepository        $eventRepository,
        private RoundRepository        $roundRepository,
        private CategoryRepository     $categoryRepository,
        private LinkHelper             $linkHelper,
        private PilotHelper            $pilotHelper,
        private SerializerInterface    $serializer,
        private EntityManagerInterface $em
    )
    {}

    public function getPilots(
        int $page,
        int $limit,
        ?string $sort = null,
        ?string $order = null,
        ?string $name = null,
        ?string $email = null,
        ?string $phone = null,
        ?int    $eventId = null,
        ?int    $roundId = null,
        ?int    $categoryId = null,
        ?string $number = null,
        ?bool   $ffsaLicensee = null,
        ?string $ffsaNumber = null,
        ?string $nationality = null,
        ?bool   $receivedWindscreenBand = null
    ): array
    {
        $personIdsTotal = $this->personRepository->findFilteredPilotPersonIdsPaginated($page, $limit, $sort, $order, $name, $email, $phone, $eventId, $roundId, $categoryId, $number, $ffsaLicensee, $ffsaNumber, $nationality, $receivedWindscreenBand);
        $persons = $this->personRepository->findPersonsByIds($personIdsTotal['items']);

        return [
            'pilots' => $persons,
            'pagination' => [
                'totalItems' => $personIdsTotal['total'],
                'pageIndex' => $page,
                'itemsPerPage' => $limit
            ]
        ];
    }

    public function createPilot(CreatePilotDto $pilotDto): Pilot
    {
        $person = $this->personRepository->find($pilotDto->personId);
        if ($pilotDto->personId === null || $person === null) {
            throw new Exception('Person not found');
        }

        $event = $this->eventRepository->find($pilotDto->eventId);
        if ($pilotDto->eventId === null || $event === null) {
            throw new Exception('Event not found');
        }

        // create pilot
        $pilot = $person->getPilots()->filter(fn(Pilot $p) => $p->getEvent()->getId() === $pilotDto->eventId)->first();
        if ($pilot === false) {
            $pilot = new Pilot();
            $pilot->setPerson($person)
                ->setEvent($event);
        }

        $pilotRoundCategories = $pilot->getPilotRoundCategories()->first();
        if ($pilotRoundCategories !== false) {
            $category = $pilotRoundCategories->getCategory();
            $pilotNumber = $this->pilotHelper->getPilotNumber($event, $category ?? null, $pilotDto->pilotNumber);
        }

        $pilot->setFfsaLicensee($pilotDto->ffsaLicensee)
            ->setFfsaNumber($pilotDto->ffsaNumber)
            ->setReceiveWindscreenBand($pilotDto->receiveWindscreenBand)
            ->setPilotNumber($pilotNumber ?? null);

        // update pilot presence
        $participations = $this->serializer->denormalize($pilotDto->participations, PilotPresenceDto::class . '[]');
        $this->updatePilotPresence($pilot, $participations);

        $this->em->persist($pilot);
        $this->em->flush();

        return $pilot;
    }

    /**
     * Update the presence of a person for each round.
     *
     * @param Person $person
     * @param PilotPresenceDto[] $presence
     */
    private function updatePersonPresence(Person $person, array $presence): void
    {
        $roundIds = [];
        $roundDetailIds = [];
        foreach ($presence as $roundPresence) {
            $round = $this->roundRepository->find($roundPresence->roundId);
            if ($round === null) {
                continue; // skip if round not found
            }

            $person->addRound($round);
            $roundIds[] = $round->getId();

            foreach ($round->getRoundDetails()->toArray() as $roundDetail) {
                $person->addRoundDetail($roundDetail);
                $roundDetailIds[] = $roundDetail->getId();
            }

            $this->em->persist($person);
        }

        // remove rounds that are not in the presence array
        $personRounds = $person->getRounds();
        foreach ($personRounds->toArray() as $round) {
            if (!in_array($round->getId(), $roundIds)) {
                $person->removeRound($round);
            }
        }

        // remove round details that are not in the presence array
        $personRoundDetails = $person->getRoundDetails();
        foreach ($personRoundDetails->toArray() as $roundDetail) {
            if (!in_array($roundDetail->getId(), $roundDetailIds)) {
                $person->removeRoundDetail($roundDetail);
            }
        }
    }

    /**
     * Update the presence of a pilot for each round.
     *
     * @param Pilot $pilot
     * @param PilotPresenceDto[] $presence
     */
    private function updatePilotPresence(Pilot $pilot, array $presence): void
    {
        $pilotRoundCategoryIds = [];
        foreach ($presence as $roundPresence) {
            $pilotRoundCategory = $pilot->getPilotRoundCategories()->filter(fn ($prc) => $prc->getRound()->getId() === $roundPresence->roundId && $prc->getCategory()->getId() === $roundPresence->categoryId)->first();
            if ($pilotRoundCategory === false) {
                $round = $this->roundRepository->find($roundPresence->roundId);
                $category = $this->categoryRepository->find($roundPresence->categoryId);

                $pilotRoundCategory = new PilotRoundCategory();
                $pilotRoundCategory->setPilot($pilot)
                    ->setRound($round)
                    ->setCategory($category)
                    ->setIsEngaged(true)
                    ->setIsCompeting(true);
                $pilot->addPilotRoundCategory($pilotRoundCategory);
            }

            $pilotRoundCategory->setVehicle($roundPresence->vehicle);
            $this->em->persist($pilotRoundCategory);

            for ($i = 1; $i < 3; $i++) {
                $qualifying = $pilotRoundCategory->getQualifyings()->filter(fn (Qualifying $q) => $q->getPassage() === $i)->first();
                if ($qualifying === false) {
                    $qualifying = new Qualifying();
                    $qualifying->setPilotRoundCategory($pilotRoundCategory)
                        ->setPassage($i)
                        ->setIsValid(true);

                    $this->em->persist($qualifying);
                }
            }

            $pilotRoundCategoryIds[] = $pilotRoundCategory->getId();
        }

        // remove pilot round categories that are not in the presence array
        $pilotRoundCategories = $pilot->getPilotRoundCategories();
        foreach ($pilotRoundCategories->toArray() as $pilotRoundCategory) {
            if (!in_array($pilotRoundCategory->getId(), $pilotRoundCategoryIds)) {
                $this->em->remove($pilotRoundCategory);
            }
        }
    }

    public function updatePersonPilot(Person $person, PilotDto $pilotDto): void
    {
        // update person
        $person->setFirstName($pilotDto->firstName)
            ->setLastName($pilotDto->lastName)
            ->setEmail($pilotDto->email)
            ->setPhone($pilotDto->phone)
            ->setComment($pilotDto->comment)
            ->setWarnings($pilotDto->warnings)
            ->setNationality($pilotDto->nationality);

        $this->em->persist($person);

        // update person presence
        $pilotPresence = $this->serializer->denormalize($pilotDto->presence, PilotPresenceDto::class . '[]');
        $this->updatePersonPresence($person, $pilotPresence);

        // update links
        if (!empty($pilotDto->instagram)) {
            $this->linkHelper->upsertInstagramLink($person, $pilotDto->instagram);
        }

        // update pilot
        if (!empty($pilotDto->eventId)) {
            $pilot = $person->getPilots()->filter(fn(Pilot $p) => $p->getEvent()->getId() === $pilotDto->eventId)->first();
            if ($pilot === false) {
                $event = $this->eventRepository->find($pilotDto->eventId);
                if ($event === null) {
                    throw new Exception('Event not found');
                }

                $pilot = new Pilot();
                $pilot->setPerson($person)
                    ->setEvent($event);

            }

            $pilot->setFfsaLicensee($pilotDto->ffsaLicensee)
                ->setFfsaNumber($pilotDto->ffsaNumber)
                ->setPilotNumber($pilotDto->pilotNumber)
                ->setReceiveWindscreenBand($pilotDto->receiveWindscreenBand);

            $this->updatePilotPresence($pilot, $pilotPresence);

            $this->em->persist($pilot);
        }

        $this->em->flush();
    }

    public function deletePersonPilot(Person $person): void
    {
        foreach ($person->getPilots() as $pilot) {
            $this->em->remove($pilot);
        }
        $this->em->flush();
    }
}