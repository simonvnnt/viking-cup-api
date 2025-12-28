<?php

namespace App\Business;

use App\Dto\BilletwebTicketDto;
use App\Entity\Event;
use App\Entity\Ticket;
use App\Entity\Category;
use App\Entity\Person;
use App\Entity\Pilot;
use App\Entity\PilotRoundCategory;
use App\Entity\Qualifying;
use App\Entity\Round;
use App\Entity\RoundDetail;
use App\Entity\Visitor;
use App\Helper\ConfigHelper;
use App\Helper\PilotHelper;
use App\Repository\TicketRepository;
use App\Repository\EventRepository;
use App\Repository\PersonRepository;
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
    private array $eventsMapping;
    private array $fieldsMapping;

    public function __construct(
        private readonly TicketRepository             $ticketRepository,
        private readonly EventRepository              $eventRepository,
        private readonly RoundRepository              $roundRepository,
        private readonly PersonRepository             $personRepository,
        private readonly PilotRoundCategoryRepository $pilotRoundCategoryRepository,
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

        $this->eventsMapping = json_decode($this->configHelper->getValue('BILLETWEB_EVENTS_MAPPING'), true);
        $this->fieldsMapping = json_decode($this->configHelper->getValue('BILLETWEB_FIELDS_MAPPING'), true);
    }

    public function syncPilots(): void
    {
        $pilotEventIds = $this->configHelper->getValue('PILOT_EVENT_IDS');
        $pilotEventIds = explode(',', $pilotEventIds);

        $pilotWildCardEventIds = $this->configHelper->getValue('PILOT_WILDCARD_EVENT_IDS');
        $pilotWildCardEventIds = explode(',', $pilotWildCardEventIds);

        foreach (array_merge($pilotEventIds, $pilotWildCardEventIds) as $pilotEventId) {
            $seasonId = $this->eventsMapping['pilot'][$pilotEventId];
            $season = $this->eventRepository->find($seasonId);

            $isWildCard = in_array($pilotEventId, $pilotWildCardEventIds);

            $eventPilotsData = $this->billetwebService->getEventAttendees($pilotEventId);

            $eventTickets = $this->serializer->denormalize($eventPilotsData, BilletwebTicketDto::class . '[]', 'json', [
                AbstractObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true
            ]);

            /** @var BilletwebTicketDto $eventTicket */
            foreach ($eventTickets as $eventTicket) {
                try {
                    $ticket = $this->createTicketFromBilletwebDto($season, $eventTicket, $isWildCard ? 'wildcard' : 'pilot');

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
                            ->setNationality($ticket->getNationality());
                    }

                    foreach ($ticket->getRounds() as $round) {
                        $person->addRound($round);

                        foreach ($round->getRoundDetails() as $roundDetail) {
                            $person->addRoundDetail($roundDetail);
                        }
                    }

                    $this->em->persist($person);

                    // Create Pilot Entity
                    $pilot = $person->getPilots()->filter(fn(Pilot $pilot) => $pilot->getEvent()->getId() === $season->getId())->first();
                    if ($pilot === false) {
                        $pilot = new Pilot();
                        $pilot->setEvent($season)
                            ->setPerson($person)
                            ->setWildCard($isWildCard)
                            ->setFfsaNumber($eventTicket->custom['Numéro de licence FFSA'] ?? null)
                            ->setFfsaLicensee(boolval($eventTicket->custom['Etes-vous licencié FFSA ?'] ?? null))
                            ->setReceiveWindscreenBand(false);

                        if ($pilot->getCreatedAt() === null && $ticket->getCreationDate() !== null) {
                            $pilot->setCreatedAt($ticket->getCreationDate());
                        }

                        $pilotNumber = $this->pilotHelper->getPilotNumber($season, $ticket->getCategory());
                        $pilot->setPilotNumber($pilotNumber);

                        $this->em->persist($pilot);

                        echo 'Nouveau pilote : ' . $ticket->getFirstName() . ' ' . $ticket->getLastName() . PHP_EOL;
                    }

                    // Create PilotRoundCategory Entity
                    $vehicle = $eventTicket->custom['Véhicule pour participer à la compétition'] ?? null;

                    foreach ($ticket->getRounds() as $round) {
                        $this->createPilotRoundCategory($ticket, $pilot, $round, $ticket->getCategory(), $vehicle);
                    }

                    $this->em->flush();
                } catch (\Throwable $e) {}
            }

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
            $seasonId = $this->eventsMapping['visitor'][$visitorEventId];
            $season = $this->eventRepository->find($seasonId);

            $eventVisitorsData = $this->billetwebService->getEventAttendees($visitorEventId);

            $eventTickets = $this->serializer->denormalize($eventVisitorsData, BilletwebTicketDto::class . '[]', 'json', [
                AbstractObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true
            ]);

            foreach ($eventTickets as $eventTicket) {
                try {
                    $ticket = $this->createTicketFromBilletwebDto($season, $eventTicket, 'visitor');

                    // Skip if the ticket is a pack
                    if ($ticket === null || $ticket->isPack() || $ticket->getTicketLabel() === 'Pass Viking!Cup enfant') {
                        continue;
                    }

                    $email = trim($ticket->getEmail()) ?? trim($ticket->getBuyerEmail());

                    if (empty($email)) {
                        continue; // Ignore les tickets sans email
                    }

                    if ($ticket->getRounds()->isEmpty() || $ticket->getRoundDetails()->isEmpty()) {
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

                    foreach ($ticket->getRounds() as $round) {
                        $person->addRound($round);
                    }

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
                } catch (\Throwable $e) {}
            }


            $this->em->flush();
        }
    }

    private function createTicketFromBilletwebDto(Event $event, BilletwebTicketDto $billetwebDto, string $ticketType): ?Ticket
    {
        $ticket = $this->ticketRepository->findBy(['externalId' => $billetwebDto->id]);

        if (count($ticket) > 0) {
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

        $fieldsMapping = $this->fieldsMapping[$event->getId()][$ticketType];

        if ($ticketType === 'visitor') {
            $roundField = $fieldsMapping['round'];
            $roundDetailField = $fieldsMapping['roundDetail'];

            if (!str_contains(strtolower($billetwebDto->$roundDetailField), 'enfant')
                && !str_contains(strtolower($billetwebDto->$roundField), 'enfant')
                && $roundField) {
                $roundName = explode(' - ', trim($billetwebDto->$roundField))[0] ?? null;

                $round = $this->roundRepository->findOneBy(['event' => $event, 'name' => $roundName]);
                $ticket->addRound($round);

                if (str_contains(strtolower($billetwebDto->$roundDetailField), 'week-end')) {
                    $roundDetails = $round->getRoundDetails();
                } else {
                    $roundDetails = $round->getRoundDetails()->filter(fn(RoundDetail $roundDetail) => str_contains($billetwebDto->$roundDetailField, $roundDetail->getName()));
                }

                foreach ($roundDetails->toArray() as $roundDetail) {
                    $ticket->addRoundDetail($roundDetail);
                }
            }
        } else {
            $categoryField = $fieldsMapping['category'];
            $category = $event->getCategories()->filter(fn(Category $category) => str_contains(strtoupper(trim($billetwebDto->$categoryField)), strtoupper($category->getName())))->first();

            if (!$ticket->isPack()) {
                $roundField = $fieldsMapping['round'];
                if ($roundField) {
                    $roundName = explode(' - ', trim($billetwebDto->$roundField))[0] ?? null;
                    $round = $this->roundRepository->findOneBy(['event' => $event, 'name' => trim($roundName)]);

                    $ticket->addRound($round);
                } else {
                    foreach ($event->getRounds() as $round) {
                        $ticket->addRound($round);
                    }
                }
            }
        }

        $ticket->setCategory($category ?? null);

        $this->em->persist($ticket);

        return $ticket;
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