<?php

namespace App\Business;

use App\Dto\BilletwebTicketDto;
use App\Entity\Ticket;
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
use App\Repository\TicketRepository;
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
        private readonly TicketRepository             $ticketRepository,
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

        foreach ($pilotEventIds as $pilotEventId) {
            $eventPilotsData = $this->billetwebService->getEventAttendees($pilotEventId);

            $eventTickets = $this->serializer->denormalize($eventPilotsData, BilletwebTicketDto::class . '[]', 'json', [
                AbstractObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true
            ]);

            $doubleMountingTickets = [];
            /** @var BilletwebTicketDto $eventTicket */
            foreach ($eventTickets as $eventTicket) {
                try {
                    $ticket = $this->createTicketFromBilletwebDto($eventTicket, 'pilot');

                    if ($ticket === null || $ticket->isPack()) {
                        continue;
                    }

                    // Create Person Entity
                    $person = $this->personRepository->findByFirstNameLastName($ticket->getFirstName(), $ticket->getLastName());
                    if ($person === null) {
                        $person = new Person();
                        $person->setFirstName($ticket->getFirstName())
                            ->setLastName($ticket->getLastName())
                            ->setEmail($ticket->getEmail())
                            ->setPhone($ticket->getPhone())
                            ->setAddress($ticket->getAddress())
                            ->setZipCode($ticket->getZipCode())
                            ->setCity($ticket->getCity())
                            ->setCountry($ticket->getCountry())
                            ->setNationality($ticket->getNationality())
                            ->addRound($ticket->getRound());
                    }

                    foreach ($ticket->getRound()->getRoundDetails() as $roundDetail) {
                        $person->addRoundDetail($roundDetail);
                    }

                    $this->em->persist($person);

                    // Create Pilot Entity
                    $pilot = $person->getPilot();
                    if ($pilot === null) {
                        $pilot = new Pilot();
                        $pilot->setPerson($person)
                            ->setFfsaLicensee(boolval($eventTicket->custom['Etes-vous licencié FFSA ?'] ?? null));

                        if ($pilot->getCreatedAt() === null && $ticket->getCreationDate() !== null) {
                            $pilot->setCreatedAt($ticket->getCreationDate());
                        }

                        $this->em->persist($pilot);

                        echo 'Nouveau pilote : ' . $ticket->getFirstName() . ' ' . $ticket->getLastName() . PHP_EOL;
                    }

                    $pilotEvent = $this->pilotEventRepository->findOneBy(['pilot' => $pilot, 'event' => $ticket->getRound()?->getEvent()]);
                    if ($pilotEvent === null) {
                        $pilotEvent = new PilotEvent();
                        $pilotEvent->setPilot($pilot)
                            ->setEvent($ticket->getRound()?->getEvent())
                            ->setReceiveWindscreenBand(false);

                        $pilotNumber = $this->pilotHelper->getPilotNumber($ticket->getRound()?->getEvent(), $ticket->getCategory());
                        $pilotEvent->setPilotNumber($pilotNumber);

                        $this->em->persist($pilotEvent);
                    }

                    // Create PilotRoundCategory Entity
                    $doubleMounting = boolval($eventTicket->custom['Double monte '] ?? null);
                    $vehicle = $eventTicket->custom['Véhicule pour participer à la compétition'] ?? null;

                    if ($doubleMounting === true) {
                        $doubleMountingTickets[] = [
                            'ticket' => $ticket,
                            'pilot' => $pilot,
                            'round' => $ticket->getRound(),
                            'category' => $ticket->getCategory(),
                            'vehicle' => $vehicle,
                            'mainPilotName' => $eventTicket->custom['Nom du pilote principal'] ?? null
                        ];
                    } else {
                        $this->createPilotRoundCategory($ticket, $pilot, $ticket->getRound(), $ticket->getCategory(), $vehicle);
                    }

                    $this->em->flush();
                } catch (\Throwable $e) {
                    $t = $e->getMessage();
                }
            }


            $this->createDoubleMountPilotRoundCategory($doubleMountingTickets);
            $this->em->flush();
        }
    }
    public function syncVisitors(): void
    {
        $visitorEventIds = $this->configHelper->getValue('VISITOR_EVENT_IDS');
        $visitorEventIds = explode(',', $visitorEventIds);

        $persons = [];
        $companions = [];

        foreach ($visitorEventIds as $visitorEventId) {
            $eventVisitorsData = $this->billetwebService->getEventAttendees($visitorEventId);

            $eventTickets = $this->serializer->denormalize($eventVisitorsData, BilletwebTicketDto::class . '[]', 'json', [
                AbstractObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true
            ]);

            foreach ($eventTickets as $key => $eventTicket) {
                try {
                    $ticket = $this->createTicketFromBilletwebDto($eventTicket, 'visitor');

                    // Skip if the ticket is a pack
                    if ($ticket === null || $ticket->isPack() || $ticket->getTicketLabel() === 'Pass Viking!Cup enfant') {
                        continue;
                    }

                    $email = trim($ticket->getEmail()) ?? trim($ticket->getBuyerEmail());

                    if (empty($email)) {
                        continue; // Ignore les tickets sans email
                    }

                    if ($ticket->getRound() === null || $ticket->getRoundDetails()->isEmpty()) {
                        continue;
                    }

                    // if the person does not exist, create it
                    if (!isset($persons[$email])) {
                        $person = $this->personRepository->findByEmail($ticket->getEmail());
                        if ($person === null) {
                            $person = new Person();
                            $person->setFirstName($ticket->getBuyerFirstName())
                                ->setLastName($ticket->getBuyerLastName())
                                ->setEmail($ticket->getEmail());

                            $this->em->persist($person);
                        }

                        $persons[$email] = $person;
                    }
                    $person = $persons[$email];

                    $person->addRound($ticket->getRound());

                    foreach ($ticket->getRoundDetails()->toArray() as $roundDetail) {
                        $person->addRoundDetail($roundDetail);

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
                                ->setRegistrationDate($ticket->getCreationDate());

                            $person->addVisitor($visitor);

                            $this->em->persist($person);
                        }

                        $visitor->setCompanions($companions[$email][$roundDetail->getId()])
                            ->addTicket($ticket);
                        $this->em->persist($visitor);
                    }
                } catch (\Throwable $e) {
                    $t = $e->getMessage();
                }
            }


            $this->em->flush();
        }
    }

    private function createTicketFromBilletwebDto(BilletwebTicketDto $billetwebDto, string $ticketType): ?Ticket
    {
        $ticket = $this->ticketRepository->find($billetwebDto->id);

        if ($ticket !== null) {
            return null;
        }

        $ticket = new Ticket();
        $ticket->setExternalId($billetwebDto->id)
            ->setTicketNumber($billetwebDto->extId)
            ->setBarcode($billetwebDto->barcode)
            ->setCreationDate(new \DateTime($billetwebDto->orderDate))
            ->setTicketLabel($billetwebDto->ticket)
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
            ->setPass((int)$billetwebDto->pass === -1 ? null : (int)$billetwebDto->pass)
            ->setPack((int)$billetwebDto->pass === -1)
            ->setAddress($billetwebDto->custom['Adresse'] ?? null)
            ->setCity($billetwebDto->custom['Ville'] ?? null)
            ->setZipCode($billetwebDto->custom['Code postal'] ?? null)
            ->setCountry($billetwebDto->custom['Pays'] ?? null)
            ->setNationality($billetwebDto->custom['Nationalité'] ?? null)
            ->setPhone($billetwebDto->custom['Portable'] ?? null);

        if ($ticketType === 'pilot') {
            $category = $this->categoryRepository->findOneBy(['name' => trim($billetwebDto->category)]);

            if (!$ticket->isPack()) {
                // TODO get event to find round
                $round = $this->roundRepository->findOneBy(['name' => $ticket->getTicketLabel()]);
            }
        } else if ($ticketType === 'visitor') {
            if (!str_contains(strtolower($billetwebDto->ticket), 'enfant') && !str_contains(strtolower($billetwebDto->category), 'enfant')) {
                // TODO get event to find round
                $round = $this->roundRepository->findOneBy(['name' => trim($billetwebDto->category)]);

                if (str_contains(strtolower($billetwebDto->ticket), 'week-end')) {
                    $roundDetails = $round->getRoundDetails();
                } else {
                    $roundDetails = $round->getRoundDetails()->filter(fn(RoundDetail $roundDetail) => str_contains($billetwebDto->ticket, $roundDetail->getName()));
                }

                foreach ($roundDetails->toArray() as $roundDetail) {
                    $ticket->addRoundDetail($roundDetail);
                }
            }
        }

        $ticket->setCategory($category ?? null)
            ->setRound($round ?? null);

        $this->em->persist($ticket);

        return $ticket;
    }

    private function createDoubleMountPilotRoundCategory(array $doubleMountingTickets): void
    {
        $pilotAssociation = [];
        foreach ($doubleMountingTickets as $doubleMountingTicket) {
            $mainPilot = $this->pilotRepository->findByName($doubleMountingTicket['mainPilotName']);
            if ($mainPilot === null) {
                continue;
            }

            if ($mainPilot->getId() !== $doubleMountingTicket['pilot']->getId()) {
                $this->createPilotRoundCategory(
                    $doubleMountingTicket['ticket'],
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
            $mainPilot = $this->pilotRepository->findByName($doubleMountingTicket['mainPilotName']);
            if ($mainPilot === null) {
                continue;
            }

            if ($mainPilot->getId() === $doubleMountingTicket['pilot']->getId()) {
                $this->createPilotRoundCategory(
                    $doubleMountingTicket['ticket'],
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

    private function createPilotRoundCategory(Ticket $ticket, Pilot $pilot, Round $round, Category $category, ?string $vehicle, bool $isMainPilot = true, ?Pilot $secondPilot = null): void
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
        $pilotRoundCategory->setSecondPilot($secondPilot)
            ->addTicket($ticket);

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