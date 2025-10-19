<?php

namespace App\Business;

use App\Dto\BilletwebTicketDto;
use App\Entity\BilletwebTicket;
use App\Entity\Category;
use App\Entity\Person;
use App\Entity\Pilot;
use App\Entity\PilotEvent;
use App\Entity\PilotRoundCategory;
use App\Entity\Qualifying;
use App\Entity\Round;
use App\Entity\RoundDetail;
use App\Entity\Visitor;
use App\Helper\ConfigHelper;
use App\Helper\PilotHelper;
use App\Repository\BilletwebTicketRepository;
use App\Repository\CategoryRepository;
use App\Repository\EventRepository;
use App\Repository\PersonRepository;
use App\Repository\PilotEventRepository;
use App\Repository\PilotRepository;
use App\Repository\PilotRoundCategoryRepository;
use App\Repository\QualifyingRepository;
use App\Repository\RoundRepository;
use App\Service\BilletwebService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

class BilletwebBusiness
{
    public function __construct(
        private readonly BilletwebTicketRepository    $billetwebRepository,
        private readonly EventRepository              $eventRepository,
        private readonly RoundRepository              $roundRepository,
        private readonly CategoryRepository           $categoryRepository,
        private readonly PersonRepository             $personRepository,
        private readonly PilotRepository              $pilotRepository,
        private readonly PilotRoundCategoryRepository $pilotRoundCategoryRepository,
        private readonly PilotEventRepository         $pilotEventRepository,
        private readonly QualifyingRepository         $qualifyingRepository,
        private readonly BilletwebService             $billetwebService,
        private readonly ConfigHelper                 $configHelper,
        private readonly PilotHelper                  $pilotHelper,
        private readonly EntityManagerInterface       $em,
        private SerializerInterface                   $serializer
    )
    {
        $normalizer = new ObjectNormalizer(null, new CamelCaseToSnakeCaseNameConverter());
        $arrayDenormalizer = new ArrayDenormalizer();
        $this->serializer = new Serializer([$normalizer, $arrayDenormalizer]);
    }

    public function syncPilots(): void
    {
        $pilotEventIds = $this->configHelper->getValue('PILOT_EVENT_IDS');
        $pilotEventIds = explode(',', $pilotEventIds);
        // Get Event Entity
        $event = $this->eventRepository->find(1);

        foreach ($pilotEventIds as $pilotEventId) {
            $eventPilotsData = $this->billetwebService->getEventAttendees($pilotEventId);

            $eventTickets = $this->serializer->denormalize($eventPilotsData, BilletwebTicketDto::class . '[]', 'json', [
                AbstractObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true
            ]);

            $doubleMountingTickets = [];
            foreach ($eventTickets as $eventTicket) {
                try {
                    $billetwebTicket = $this->createBilletwebTicketFromDto($eventTicket);

                    if ($billetwebTicket === null || $billetwebTicket->getPass() === -1) {
                        continue;
                    }

                    // Get Round Entity
                    $round = $this->roundRepository->findOneBy(['event' => $event, 'name' => $billetwebTicket->getTicketLabel()]);

                    // Get Category Entity
                    $category = $this->categoryRepository->findOneBy(['name' => $billetwebTicket->getCategory()]);

                    // Create Pilot Entity
                    $person = $this->personRepository->findByFirstNameLastName($billetwebTicket->getFirstName(), $billetwebTicket->getLastName());
                    if ($person === null) {
                        $person = new Person();
                        $person->setFirstName($billetwebTicket->getFirstName())
                            ->setLastName($billetwebTicket->getLastName())
                            ->setEmail($billetwebTicket->getEmail())
                            ->setPhone($billetwebTicket->getCustom()['Portable'] ?? null)
                            ->setAddress($billetwebTicket->getCustom()['Adresse'] ?? null)
                            ->setZipCode($billetwebTicket->getCustom()['Code postal'] ?? null)
                            ->setCity($billetwebTicket->getCustom()['Ville'] ?? null)
                            ->setCountry($billetwebTicket->getCustom()['Pays'] ?? null)
                            ->setNationality($billetwebTicket->getCustom()['Nationalité'] ?? null)
                            ->addRound($round);
                    }

                    foreach ($round->getRoundDetails() as $roundDetail) {
                        $person->addRoundDetail($roundDetail);
                    }

                    $this->em->persist($person);

                    $pilot = $person->getPilot();
                    if ($pilot === null) {
                        $pilot = new Pilot();
                        $pilot->setPerson($person)
                            ->setFfsaLicensee(boolval($billetwebTicket->getCustom()['Etes-vous licencié FFSA ?'] ?? null));

                        if ($pilot->getCreatedAt() === null && $billetwebTicket->getCreationDate() !== null) {
                            $pilot->setCreatedAt($billetwebTicket->getCreationDate());
                        }

                        $this->em->persist($pilot);

                        echo 'Nouveau pilote : ' . $billetwebTicket->getFirstName() . ' ' . $billetwebTicket->getLastName() . PHP_EOL;
                    }

                    $pilotEvent = $this->pilotEventRepository->findOneBy(['pilot' => $pilot, 'event' => $round->getEvent()]);
                    if ($pilotEvent === null) {
                        $pilotEvent = new PilotEvent();
                        $pilotEvent->setPilot($pilot)
                            ->setEvent($round->getEvent())
                            ->setReceiveWindscreenBand(false);

                        $pilotNumber = $this->pilotHelper->getPilotNumber($round->getEvent(), $category);
                        $pilotEvent->setPilotNumber($pilotNumber);

                        $this->em->persist($pilotEvent);
                    }

                    // Create PilotRoundCategory Entity
                    $doubleMounting = boolval($billetwebTicket->getCustom()['Double monte '] ?? null);
                    $vehicle = $billetwebTicket->getCustom()['Véhicule pour participer à la compétition'] ?? null;

                    if ($doubleMounting === true) {
                        $doubleMountingTickets[] = [
                            'ticket' => $billetwebTicket,
                            'pilot' => $pilot,
                            'round' => $round,
                            'category' => $category,
                            'vehicle' => $vehicle
                        ];
                    } else {
                        $this->createPilotRoundCategory($pilot, $round, $category, $vehicle);
                    }

                    $this->em->flush();
                } catch (\Throwable $e) {}
            }


            $this->createDoubleMountPilotRoundCategory($doubleMountingTickets);
            $this->em->flush();
        }
    }
    public function syncVisitors(): void
    {
        $visitorEventIds = $this->configHelper->getValue('VISITOR_EVENT_IDS');
        $visitorEventIds = explode(',', $visitorEventIds);
        $event = $this->eventRepository->find(1);
        $rounds = [];

        $persons = [];
        $companions = [];

        foreach ($visitorEventIds as $visitorEventId) {
            $eventVisitorsData = $this->billetwebService->getEventAttendees($visitorEventId);

            $eventTickets = $this->serializer->denormalize($eventVisitorsData, BilletwebTicketDto::class . '[]', 'json', [
                AbstractObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true
            ]);

            foreach ($eventTickets as $key => $eventTicket) {
                try {
                    $billetwebTicket = $this->createBilletwebTicketFromDto($eventTicket);

                    // Skip if the ticket is a pack
                    if ($billetwebTicket === null || $billetwebTicket->getPass() === -1 || $billetwebTicket->getTicketLabel() === 'Pass Viking!Cup enfant') {
                        continue;
                    }

                    $email = trim($billetwebTicket->getEmail()) ?? trim($billetwebTicket->getBuyerEmail());

                    if (empty($email)) {
                        continue; // Ignore les tickets sans email
                    }

                    $category = trim($billetwebTicket->getCategory());
                    if (!isset($rounds[$category])) {
                        $rounds[$category] = $this->roundRepository->findOneBy(['event' => $event, 'name' => $billetwebTicket->getCategory()]);
                    }
                    $round = $rounds[$category];
                    if ($round === null) {
                        continue;
                    }

                    if (str_contains(strtolower($billetwebTicket->getTicketLabel()), 'week-end')) {
                        $roundDetails = $round->getRoundDetails();
                    } else {
                        $roundDetails = $round->getRoundDetails()->filter(fn(RoundDetail $roundDetail) => str_contains($billetwebTicket->getTicketLabel(), $roundDetail->getName()));
                    }

                    if ($roundDetails->isEmpty()) {
                        continue;
                    }

                    foreach ($roundDetails->toArray() as $roundDetail) {
                        // if the person does not exist, create it
                        if (!isset($persons[$email])) {
                            $person = $this->personRepository->findByEmail($billetwebTicket->getEmail());
                            if ($person === null) {
                                $person = new Person();
                                $person->setFirstName($billetwebTicket->getBuyerFirstName())
                                    ->setLastName($billetwebTicket->getBuyerLastName())
                                    ->setEmail($billetwebTicket->getEmail());

                                $this->em->persist($person);
                            }

                            $persons[$email] = $person;
                        }
                        $person = $persons[$email];

                        $person->addRound($round)
                            ->addRoundDetail($roundDetail);

                        if (!isset($companions[$email][$roundDetail->getId()])) {
                            $companions[$email][$roundDetail->getId()] = 0;
                        } else {
                            $companions[$email][$roundDetail->getId()]++;
                        }

                        // if the visitor does not exist, create it
                        $visitor = $person->getVisitors()->filter(fn(Visitor $visitor) => $visitor->getRoundDetail()->getId() === $roundDetail->getId())->first();
                        if ($visitor === false) {
                            $visitor = new Visitor();
                            $visitor->setPerson($person)
                                ->setRoundDetail($roundDetail)
                                ->setRegistrationDate($billetwebTicket->getCreationDate());

                            $person->addVisitor($visitor);

                            $this->em->persist($person);
                        }

                        $visitor->setCompanions($companions[$email][$roundDetail->getId()]);
                        $this->em->persist($visitor);
                    }
                } catch (\Throwable $e) {
                    $t = $e->getMessage();
                }
            }


            $this->em->flush();
        }
    }

    private function createBilletwebTicketFromDto(BilletwebTicketDto $billetwebDto): ?BilletwebTicket
    {
        $billetweb = $this->billetwebRepository->find($billetwebDto->id);

        if ($billetweb !== null) {
            return null;
        }

        $billetweb = new BilletwebTicket();
        $billetweb->setId($billetwebDto->id)
            ->setTicketNumber($billetwebDto->extId)
            ->setBarcode($billetwebDto->barcode)
            ->setCreationDate(new \DateTime($billetwebDto->orderDate))
            ->setTicketLabel($billetwebDto->ticket)
            ->setCategory($billetwebDto->category)
            ->setLastName($billetwebDto->name)
            ->setFirstName($billetwebDto->firstname)
            ->setEmail($billetwebDto->email)
            ->setBuyerLastName($billetwebDto->orderName)
            ->setBuyerFirstName($billetwebDto->orderFirstname)
            ->setBuyerEmail($billetwebDto->orderEmail)
            ->setOrderNumber($billetwebDto->orderExtId)
            ->setPaymentType($billetwebDto->orderPaymentType)
            ->setAmount($billetwebDto->price)
            ->setPaid($billetwebDto->orderPaid)
            ->setUsed($billetwebDto->used)
            ->setUsedDate(!empty($billetwebDto->usedDate) && $billetwebDto->usedDate !== '0000-00-00 00:00:00' ? new \DateTime($billetwebDto->usedDate) : null)
            ->setPass((int)$billetwebDto->pass)
            ->setCustom($billetwebDto->custom ?? [])
            ->setPack((int)$billetwebDto->pass === -1);

        $this->em->persist($billetweb);

        return $billetweb;
    }

    private function createDoubleMountPilotRoundCategory(array $doubleMountingTickets): void
    {
        $pilotAssociation = [];
        foreach ($doubleMountingTickets as $doubleMountingTicket) {
            $mainPilotName = $doubleMountingTicket['ticket']->getCustom()['Nom du pilote principal'] ?? null;

            $mainPilot = $this->pilotRepository->findByName($mainPilotName);
            if ($mainPilot === null) {
                continue;
            }

            if ($mainPilot->getId() !== $doubleMountingTicket['pilot']->getId()) {
                $this->createPilotRoundCategory(
                    $doubleMountingTicket['pilot'],
                    $doubleMountingTicket['round'],
                    $doubleMountingTicket['category'],
                    $doubleMountingTicket['vehicle'],
                    false
                );

                $pilotAssociation[$mainPilot->getId()] = $doubleMountingTicket['pilot'];
            }
        }

        foreach ($doubleMountingTickets as $doubleMountingTicket) {
            $mainPilotName = $doubleMountingTicket['ticket']->getCustom()['Nom du pilote principal'] ?? null;

            $mainPilot = $this->pilotRepository->findByName($mainPilotName);
            if ($mainPilot === null) {
                continue;
            }

            if ($mainPilot->getId() === $doubleMountingTicket['pilot']->getId()) {
                $this->createPilotRoundCategory(
                    $doubleMountingTicket['pilot'],
                    $doubleMountingTicket['round'],
                    $doubleMountingTicket['category'],
                    $doubleMountingTicket['vehicle'],
                    true,
                    $pilotAssociation[$doubleMountingTicket['pilot']->getId()] ?? null
                );
            }
        }

    }

    private function createPilotRoundCategory(Pilot $pilot, Round $round, Category $category, ?string $vehicle, bool $isMainPilot = true, ?Pilot $secondPilot = null): void
    {
        $pilotRoundCategory = $this->pilotRoundCategoryRepository->findOneBy(['pilot' => $pilot, 'round' => $round, 'category' => $category]);
        if ($pilotRoundCategory === null) {
            $pilotRoundCategory = new PilotRoundCategory();
            $pilotRoundCategory->setPilot($pilot)
                ->setRound($round)
                ->setCategory($category)->setVehicle($vehicle)
                ->setMainPilot($isMainPilot)
                ->setIsEngaged(true)
                ->setIsCompeting(true);

        }
        $pilotRoundCategory->setSecondPilot($secondPilot);
        $this->em->persist($pilotRoundCategory);

        for ($i = 1; $i < 3; $i++) {
            $qualifying = $this->qualifyingRepository->findOneBy(['pilotRoundCategory' => $pilotRoundCategory, 'passage' => $i]);
            if ($qualifying === null) {
                $qualifying = new Qualifying();
                $qualifying->setPilotRoundCategory($pilotRoundCategory)
                    ->setPassage($i)
                    ->setIsValid(true);

                $this->em->persist($qualifying);
            }
        }
    }
}