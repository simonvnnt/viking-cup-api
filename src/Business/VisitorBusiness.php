<?php

namespace App\Business;

use App\Dto\CreateVisitorDto;
use App\Entity\Visitor;
use App\Entity\Person;
use App\Repository\PersonRepository;
use App\Repository\RoundDetailRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Serializer\SerializerInterface;

readonly class VisitorBusiness
{
    public function __construct(
        private PersonRepository       $personRepository,
        private RoundDetailRepository  $roundDetailRepository,
        private SerializerInterface    $serializer,
        private EntityManagerInterface $em
    )
    {}

    public function getVisitors(
        int $page,
        int $limit,
        ?string $sort = null,
        ?string $order = null,
        ?int    $eventId = null,
        ?int    $roundId = null,
        ?int    $roundDetailId = null,
        ?string $name = null,
        ?string $email = null,
        ?string $phone = null,
        ?int    $fromCompanions = null,
        ?int    $toCompanions = null,
        ?string $fromDate = null,
        ?string $toDate = null
    ): array
    {
        $personIdsTotal = $this->personRepository->findFilteredVisitorPersonIdsPaginated($page, $limit, $sort, $order, $eventId, $roundId, $roundDetailId, $name, $email, $phone, $fromCompanions, $toCompanions, $fromDate, $toDate);
        $persons = $this->personRepository->findPersonsByIds($personIdsTotal['items']);

        $visitorPersons = [];
        /** @var Person $person */
        foreach ($persons as $person) {
            $personArray = $this->serializer->normalize($person, 'json', ['groups' => ['person', 'personRoundDetails', 'roundDetail', 'personLinks', 'link', 'linkLinkType', 'linkType']]);

            $visitors = $person->getVisitors()->filter(function (Visitor $visitor) use ($eventId, $roundId, $roundDetailId, $fromCompanions, $toCompanions, $fromDate, $toDate) {
                return (!$eventId || $visitor->getRoundDetail()->getRound()->getEvent()->getId() === $eventId) &&
                    (!$roundId || $visitor->getRoundDetail()->getRound()->getId() === $roundId) &&
                    (!$roundDetailId || $visitor->getRoundDetail()->getId() === $roundDetailId) &&
                    (!$fromCompanions || $visitor->getCompanions() >= $fromCompanions) &&
                    (!$toCompanions || $visitor->getCompanions() <= $toCompanions) &&
                    (!$fromDate || $visitor->getRegistrationDate() >= new \DateTime($fromDate)) &&
                    (!$toDate || $visitor->getRegistrationDate() <= new \DateTime($toDate));
            });

            $personArray['visitors'] = array_values($visitors->toArray());

            if (!empty($personArray['visitors'])) {
                $visitorPersons[] = $personArray;
            }
        }

        return [
            'pagination' => [
                'totalItems' => $personIdsTotal['total'],
                'pageIndex' => $page,
                'itemsPerPage' => $limit
            ],
            'visitors' => $visitorPersons
        ];
    }

    public function createVisitor(CreateVisitorDto $visitorDto): ?Visitor
    {
        $person = $this->personRepository->find($visitorDto->personId);
        if ($visitorDto->personId === null || $person === null) {
            throw new Exception('Person not found');
        }

        $roundDetail = $this->roundDetailRepository->find($visitorDto->roundDetailId);
        if ($visitorDto->roundDetailId === null || $roundDetail === null) {
            throw new Exception('Round detail not found');
        }

        // get round detail visitor or create a new one
        $visitor = $person->getVisitors()->filter(fn(Visitor $visitor) => $visitor->getRoundDetail()?->getId() === $roundDetail->getId())->first();
        if ($visitor === false) {
            $visitor = new Visitor();
            $visitor->setPerson($person)
                ->setRoundDetail($roundDetail)
                ->setRegistrationDate(new \DateTime('now', new \DateTimeZone('Europe/Paris')));
        }
        $visitor->setCompanions($visitorDto->companions);

        $this->em->persist($visitor);
        $this->em->flush();

        return $visitor;
    }

    public function deleteVisitor(Visitor $visitor): void
    {
        $this->em->remove($visitor);
        $this->em->flush();
    }
}