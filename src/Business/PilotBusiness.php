<?php

namespace App\Business;

use App\Dto\CreatePilotDto;
use App\Dto\PilotDto;
use App\Dto\PilotPresenceDto;
use App\Entity\Category;
use App\Entity\Event;
use App\Entity\Pilot;
use App\Entity\Person;
use App\Entity\PilotEvent;
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

        // get pilot or create new one
        $pilot = $person->getPilot();
        if ($pilot === null) {
            $pilot = new Pilot();
            $pilot->setPerson($person);
        }

        $pilot->setFfsaLicensee($pilotDto->ffsaLicensee)
            ->setFfsaNumber($pilotDto->ffsaNumber);

        $participations = $this->serializer->denormalize($pilotDto->participations, PilotPresenceDto::class . '[]');
        $this->updatePilotPresence($pilot, $participations);

        $this->em->persist($pilot);

        // create pilot event
        if (!empty($pilotDto->eventId)) {
            $event = $this->eventRepository->find($pilotDto->eventId);
            $pilotEvent = $pilot->getPilotEvents()->filter(fn($pe) => $pe->getEvent()->getId() === $pilotDto->eventId)->first();
            if ($pilotEvent === false) {
                if ($event) {
                    $pilotEvent = new PilotEvent();
                    $pilotEvent->setPilot($pilot)
                        ->setEvent($event);
                }
            }
            if ($pilotEvent !== false) {
                $pilotRoundCategories = $pilot->getPilotRoundCategories()->first();
                if ($pilotRoundCategories !== false) {
                    $category = $pilotRoundCategories->getCategory();
                    $pilotNumber = $this->pilotHelper->getPilotNumber($event, $category ?? null, $pilotDto->number);
                }

                $pilotEvent->setPilotNumber($pilotNumber ?? null)
                    ->setReceiveWindscreenBand($pilotDto->receiveWindscreenBand);

                $this->em->persist($pilotEvent);
            }
        }

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

        // remove rounds and round details that are not in the presence array
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

    public function updatePersonPilot(Pilot $pilot, PilotDto $pilotDto): void
    {
        // update person
        $person = $pilot->getPerson();
        $pilotPresence = $this->serializer->denormalize($pilotDto->presence, PilotPresenceDto::class . '[]');

        $person->setFirstName($pilotDto->firstName)
            ->setLastName($pilotDto->lastName)
            ->setEmail($pilotDto->email)
            ->setPhone($pilotDto->phone)
            ->setComment($pilotDto->comment)
            ->setWarnings($pilotDto->warnings)
            ->setNationality($pilotDto->nationality);

        $this->em->persist($person);

        $this->updatePersonPresence($person, $pilotPresence);

        if (!empty($pilotDto->instagram)) {
            $this->linkHelper->upsertInstagramLink($person, $pilotDto->instagram);
        }

        // update pilot
        $pilot->setFfsaLicensee($pilotDto->ffsaLicensee)
            ->setFfsaNumber($pilotDto->ffsaNumber);

        $this->em->persist($pilot);

        $this->updatePilotPresence($pilot, $pilotPresence);

        // update pilot event
        if (!empty($pilotDto->eventId)) {
            $pilotEvent = $pilot->getPilotEvents()->filter(fn($pe) => $pe->getEvent()->getId() === $pilotDto->eventId)->first();
            if ($pilotEvent === false) {
                $event = $this->eventRepository->find($pilotDto->eventId);
                if ($event) {
                    $pilotEvent = new PilotEvent();
                    $pilotEvent->setPilot($pilot)
                        ->setEvent($event);
                }
            }
            if ($pilotEvent !== false) {
                $pilotEvent->setPilotNumber($pilotDto->number)
                    ->setReceiveWindscreenBand($pilotDto->receiveWindscreenBand);

                $this->em->persist($pilotEvent);
            }
        }

        $this->em->flush();
    }

    public function deletePilot(Pilot $pilot): void
    {
        $this->em->remove($pilot);
        $this->em->flush();
    }
}