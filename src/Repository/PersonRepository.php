<?php

namespace App\Repository;

use App\Entity\Person;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Person>
 */
class PersonRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Person::class);
    }

    public function findByFirstNameLastName(?string $firstName, ?string $lastName): ?Person
    {
        $normalizedFirstName = $this->normalize($firstName);
        $normalizedLastName = $this->normalize($lastName);

        $qb = $this->createQueryBuilder('p')
            ->where('LOWER(p.firstName) LIKE :firstName')
            ->andWhere('LOWER(p.lastName) LIKE :lastName')
            ->setParameter('firstName', "%$normalizedFirstName%")
            ->setParameter('lastName', "%$normalizedLastName%");

        $qb->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function findByEmail(string $email): ?Person
    {
        $normalizedEmail = $this->normalize($email);

        $qb = $this->createQueryBuilder('p')
            ->where('LOWER(p.email) LIKE :email')
            ->setParameter('email', "%$normalizedEmail%");

        $qb->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function findPersonsPaginated(
        ?string $sort = null,
        ?string $order = null,
        ?string $person = null
    ): QueryBuilder
    {
        $order = $order ?? 'ASC';

        $qb = $this->createQueryBuilder('p');

        if ($person !== null) {
            $normalizedPerson = $this->normalize($person);
            $qb->where('LOWER(p.firstName) LIKE :person')
                ->orWhere('LOWER(p.lastName) LIKE :person')
                ->orWhere('LOWER(p.email) LIKE :person')
                ->orWhere('LOWER(p.phone) LIKE :person')
                ->setParameter('person', '%' . $normalizedPerson . '%');
        }

        switch ($sort) {
            case 'firstName':
                $qb->orderBy('p.firstName', $order);
                break;
            case 'lastName':
                $qb->orderBy('p.lastName', $order);
                break;
            case 'phone':
                $qb->orderBy('p.phone', $order);
                break;
            case 'email':
                $qb->orderBy('p.email', $order);
                break;
        }

        return $qb;
    }

    public function findFilteredMediaPersonIdsPaginated(
        int     $page = 1,
        int     $limit = 50,
        ?string $sort = null,
        ?string $order = null,
        ?int    $eventId = null,
        ?int    $roundId = null,
        ?string $name = null,
        ?string $email = null,
        ?string $phone = null,
        ?bool   $selected = null,
        ?bool   $selectedMailSent = null,
        ?bool   $eLearningMailSent = null,
        ?bool   $briefingSeen = null,
        ?bool   $generatePass = null
    ): array
    {
        $order = $order ?? 'ASC';

        $qb = $this->createQueryBuilder('p')
            ->select('DISTINCT p.id')
            ->innerJoin('p.medias', 'm');

        if ($eventId !== null) {
            $qb->innerJoin('m.round', 'r')
                ->andWhere('r.event = :eventId')
                ->setParameter('eventId', $eventId);
        }
        if ($roundId !== null) {
            $qb->andWhere('m.round = :roundId')
                ->setParameter('roundId', $roundId);
        }
        if ($name !== null) {
            $qb->andWhere('p.firstName LIKE :name OR p.lastName LIKE :name')
                ->setParameter('name', '%' . $name . '%');
        }
        if ($email !== null) {
            $qb->andWhere('p.email LIKE :email')
                ->setParameter('email', '%' . $email . '%');
        }
        if ($phone !== null) {
            $qb->andWhere('p.phone LIKE :phone')
                ->setParameter('phone', '%' . $phone . '%');
        }
        if ($selected !== null) {
            $qb->andWhere('m.selected = :selected')
                ->setParameter('selected', $selected);
        }
        if ($selectedMailSent !== null) {
            $qb->andWhere('m.selectedMailSent = :selectedMailSent')
                ->setParameter('selectedMailSent', $selectedMailSent);
        }
        if ($eLearningMailSent !== null) {
            $qb->andWhere('m.eLearningMailSent = :eLearningMailSent')
                ->setParameter('eLearningMailSent', $eLearningMailSent);
        }
        if ($briefingSeen !== null) {
            $qb->andWhere('m.briefingSeen = :briefingSeen')
                ->setParameter('briefingSeen', $briefingSeen);
        }
        if ($generatePass !== null) {
            $qb->andWhere('m.generatePass = :generatePass')
                ->setParameter('generatePass', $generatePass);
        }

        switch ($sort) {
            case 'firstName':
                $qb->orderBy('p.firstName', $order);
                break;
            case 'lastName':
                $qb->orderBy('p.lastName', $order);
                break;
            case 'phone':
                $qb->orderBy('p.phone', $order);
                break;
            case 'email':
                $qb->orderBy('p.email', $order);
                break;
        }

        // Compte total des résultats
        $countQb = clone $qb;
        $countQb->select('COUNT(DISTINCT p.id)');
        $total = (int) $countQb->getQuery()->getSingleScalarResult();

        // Récupération des résultats paginés
        $qb->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        return [
            'items' => array_column($qb->getQuery()->getResult(), 'id'),
            'total' => $total,
        ];
    }

    public function findFilteredPilotPersonIdsPaginated(
        int     $page = 1,
        int     $limit = 50,
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
        $order = $order ?? 'ASC';

        $qb = $this->createQueryBuilder('p')
            ->select('DISTINCT p.id')
            ->innerJoin('p.pilot', 'pi')
            ->leftJoin('pi.pilotEvents', 'pe', 'WITH', 'pe.event = :event')
            ->setParameter('event', $eventId);

        if ($name !== null) {
            $qb->andWhere('p.firstName LIKE :name OR p.lastName LIKE :name')
                ->setParameter('name', '%' . $name . '%');
        }
        if ($email !== null) {
            $qb->andWhere('p.email LIKE :email')
                ->setParameter('email', '%' . $email . '%');
        }
        if ($phone !== null) {
            $qb->andWhere('p.phone LIKE :phone')
                ->setParameter('phone', '%' . $phone . '%');
        }
        if ($eventId !== null && $number !== null) {
            $qb->andWhere('pe.pilotNumber = :number')
                ->setParameter('number', $number);
        }
        if ($roundId !== null || $categoryId !== null) {
            $qb->innerJoin('pi.pilotRoundCategories', 'prc');

            if ($roundId !== null) {
                $qb->andWhere('prc.round = :roundId')
                    ->setParameter('roundId', $roundId);
            }
            if ($categoryId !== null) {
                $qb->andWhere('prc.category = :categoryId')
                    ->setParameter('categoryId', $categoryId);
            }
        }
        if ($ffsaLicensee !== null) {
            $qb->andWhere('pi.ffsaLicensee = :ffsaLicensee')
                ->setParameter('ffsaLicensee', $ffsaLicensee);
        }
        if ($ffsaNumber !== null) {
            $qb->andWhere('pi.ffsaNumber LIKE :ffsaNumber')
                ->setParameter('ffsaNumber', '%' . $ffsaNumber . '%');
        }
        if ($nationality !== null) {
            $qb->andWhere('LOWER(p.nationality) LIKE :nationality')
                ->setParameter('nationality', '%' . $this->normalizeName($nationality) . '%');
        }
        if ($eventId !== null && $receivedWindscreenBand !== null) {
            $qb->andWhere('pe.receiveWindscreenBand = :receivedWindscreenBand')
                ->setParameter('receivedWindscreenBand', $receivedWindscreenBand);
        }


        switch ($sort) {
            case 'firstName':
                $qb->orderBy('p.firstName', $order);
                break;
            case 'lastName':
                $qb->orderBy('p.lastName', $order);
                break;
            case 'phone':
                $qb->orderBy('p.phone', $order);
                break;
            case 'email':
                $qb->orderBy('p.email', $order);
                break;
            case 'number':
                $qb->orderBy('pe.pilotNumber', $order);
                break;
            case 'ffsaLicensee':
                $qb->orderBy('pi.ffsaLicensee', $order);
                break;
            case 'ffsaNumber':
                $qb->orderBy('pi.ffsaNumber', $order);
                break;
            case 'nationality':
                $qb->orderBy('p.nationality', $order);
                break;
            case 'receivedWindscreenBand':
                $qb->orderBy('pe.receiveWindscreenBand', $order);
                break;
        }

        // Compte total des résultats
        $countQb = clone $qb;
        $countQb->select('COUNT(DISTINCT p.id)');
        $total = (int) $countQb->getQuery()->getSingleScalarResult();

        // Récupération des résultats paginés
        $qb->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        return [
            'items' => array_column($qb->getQuery()->getResult(), 'id'),
            'total' => $total,
        ];
    }

    public function findFilteredMemberPersonIdsPaginated(
        int     $page = 1,
        int     $limit = 50,
        ?string $sort = null,
        ?string $order = null,
        ?string $name = null,
        ?string $email = null,
        ?string $phone = null,
        ?string $roleAsso = null,
        ?string $roleVcup = null
    ): array
    {
        $order = $order ?? 'ASC';

        $qb = $this->createQueryBuilder('p')
            ->select('DISTINCT p.id')
            ->innerJoin('p.member', 'm');

        if ($name !== null) {
            $qb->andWhere('p.firstName LIKE :name OR p.lastName LIKE :name')
                ->setParameter('name', '%' . $name . '%');
        }
        if ($email !== null) {
            $qb->andWhere('p.email LIKE :email')
                ->setParameter('email', '%' . $email . '%');
        }
        if ($phone !== null) {
            $qb->andWhere('p.phone LIKE :phone')
                ->setParameter('phone', '%' . $phone . '%');
        }
        if ($roleAsso !== null) {
            $qb->andWhere('m.roleAsso LIKE :roleAsso')
                ->setParameter('roleAsso', '%' . $roleAsso . '%');
        }
        if ($roleVcup !== null) {
            $qb->andWhere('m.roleVcup LIKE :roleVcup')
                ->setParameter('roleVcup', '%' . $roleVcup . '%');
        }

        switch ($sort) {
            case 'firstName':
                $qb->orderBy('p.firstName', $order);
                break;
            case 'lastName':
                $qb->orderBy('p.lastName', $order);
                break;
            case 'phone':
                $qb->orderBy('p.phone', $order);
                break;
            case 'email':
                $qb->orderBy('p.email', $order);
                break;
            case 'roleAsso':
                $qb->orderBy('m.roleAsso', $order);
                break;
            case 'roleVcup':
                $qb->orderBy('m.roleVcup', $order);
                break;
        }

        // Compte total des résultats
        $countQb = clone $qb;
        $countQb->select('COUNT(DISTINCT p.id)');
        $total = (int) $countQb->getQuery()->getSingleScalarResult();

        // Récupération des résultats paginés
        $qb->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        return [
            'items' => array_column($qb->getQuery()->getResult(), 'id'),
            'total' => $total,
        ];
    }

    public function findFilteredCommissairePersonIdsPaginated(
        int     $page = 1,
        int     $limit = 50,
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
        $order = $order ?? 'ASC';

        $qb = $this->createQueryBuilder('p')
            ->select('DISTINCT p.id')
            ->innerJoin('p.commissaires', 'c');

        if ($eventId !== null) {
            $qb->innerJoin('c.round', 'r')
                ->andWhere('r.event = :eventId')
                ->setParameter('eventId', $eventId);
        }
        if ($roundId !== null) {
            $qb->andWhere('c.round = :roundId')
                ->setParameter('roundId', $roundId);
        }
        if ($name !== null) {
            $qb->andWhere('p.firstName LIKE :name OR p.lastName LIKE :name')
                ->setParameter('name', '%' . $name . '%');
        }
        if ($email !== null) {
            $qb->andWhere('p.email LIKE :email')
                ->setParameter('email', '%' . $email . '%');
        }
        if ($phone !== null) {
            $qb->andWhere('p.phone LIKE :phone')
                ->setParameter('phone', '%' . $phone . '%');
        }
        if ($licenceNumber !== null) {
            $qb->andWhere('c.licenceNumber LIKE :licenceNumber')
                ->setParameter('licenceNumber', '%' . $licenceNumber . '%');
        }
        if ($asaCode !== null) {
            $qb->andWhere('c.asaCode LIKE :asaCode')
                ->setParameter('asaCode', '%' . $asaCode . '%');
        }
        if ($typeId !== null) {
            $qb->andWhere('c.type = :typeId')
                ->setParameter('typeId', $typeId);
        }
        if ($isFlag !== null) {
            $qb->andWhere('c.isFlag = :isFlag')
                ->setParameter('isFlag', $isFlag);
        }

        switch ($sort) {
            case 'firstName':
                $qb->orderBy('p.firstName', $order);
                break;
            case 'lastName':
                $qb->orderBy('p.lastName', $order);
                break;
            case 'phone':
                $qb->orderBy('p.phone', $order);
                break;
            case 'email':
                $qb->orderBy('p.email', $order);
                break;
            case 'licenceNumber':
                $qb->orderBy('c.licenceNumber', $order);
                break;
            case 'asaCode':
                $qb->orderBy('c.asaCode', $order);
                break;
            case 'type':
                $qb->orderBy('ct.type', $order);
                break;
            case 'isFlag':
                $qb->orderBy('c.isFlag', $order);
                break;
        }

        // Compte total des résultats
        $countQb = clone $qb;
        $countQb->select('COUNT(DISTINCT p.id)');
        $total = (int) $countQb->getQuery()->getSingleScalarResult();

        // Récupération des résultats paginés
        $qb->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        return [
            'items' => array_column($qb->getQuery()->getResult(), 'id'),
            'total' => $total,
        ];
    }

    public function findFilteredVolunteerPersonIdsPaginated(
        int     $page = 1,
        int     $limit = 50,
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
        $order = $order ?? 'ASC';

        $qb = $this->createQueryBuilder('p')
            ->select('DISTINCT p.id')
            ->innerJoin('p.volunteers', 'v');

        if ($eventId !== null) {
            $qb->innerJoin('v.round', 'r')
                ->andWhere('r.event = :eventId')
                ->setParameter('eventId', $eventId);
        }
        if ($roundId !== null) {
            $qb->andWhere('v.round = :roundId')
                ->setParameter('roundId', $roundId);
        }
        if ($name !== null) {
            $qb->andWhere('p.firstName LIKE :name OR p.lastName LIKE :name')
                ->setParameter('name', '%' . $name . '%');
        }
        if ($email !== null) {
            $qb->andWhere('p.email LIKE :email')
                ->setParameter('email', '%' . $email . '%');
        }
        if ($phone !== null) {
            $qb->andWhere('p.phone LIKE :phone')
                ->setParameter('phone', '%' . $phone . '%');
        }
        if ($roleId !== null) {
            $qb->andWhere('v.role = :roleId')
                ->setParameter('roleId', $roleId);
        }

        switch ($sort) {
            case 'firstName':
                $qb->orderBy('p.firstName', $order);
                break;
            case 'lastName':
                $qb->orderBy('p.lastName', $order);
                break;
            case 'phone':
                $qb->orderBy('p.phone', $order);
                break;
            case 'email':
                $qb->orderBy('p.email', $order);
                break;
            case 'role':
                $qb->orderBy('v.role', $order);
                break;
        }

        // Compte total des résultats
        $countQb = clone $qb;
        $countQb->select('COUNT(DISTINCT p.id)');
        $total = (int) $countQb->getQuery()->getSingleScalarResult();

        // Récupération des résultats paginés
        $qb->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        return [
            'items' => array_column($qb->getQuery()->getResult(), 'id'),
            'total' => $total,
        ];
    }

    public function findFilteredRescuerPersonIdsPaginated(
        int     $page = 1,
        int     $limit = 50,
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
        $order = $order ?? 'ASC';

        $qb = $this->createQueryBuilder('p')
            ->select('DISTINCT p.id')
            ->innerJoin('p.rescuers', 'r');

        if ($eventId !== null) {
            $qb->innerJoin('r.round', 'ro')
                ->andWhere('ro.event = :eventId')
                ->setParameter('eventId', $eventId);
        }
        if ($roundId !== null) {
            $qb->andWhere('r.round = :roundId')
                ->setParameter('roundId', $roundId);
        }
        if ($name !== null) {
            $qb->andWhere('p.firstName LIKE :name OR p.lastName LIKE :name')
                ->setParameter('name', '%' . $name . '%');
        }
        if ($email !== null) {
            $qb->andWhere('p.email LIKE :email')
                ->setParameter('email', '%' . $email . '%');
        }
        if ($phone !== null) {
            $qb->andWhere('p.phone LIKE :phone')
                ->setParameter('phone', '%' . $phone . '%');
        }
        if ($role !== null) {
            $qb->andWhere('r.role LIKE :role')
                ->setParameter('role', '%' . $role . '%');
        }

        switch ($sort) {
            case 'firstName':
                $qb->orderBy('p.firstName', $order);
                break;
            case 'lastName':
                $qb->orderBy('p.lastName', $order);
                break;
            case 'phone':
                $qb->orderBy('p.phone', $order);
                break;
            case 'email':
                $qb->orderBy('p.email', $order);
                break;
            case 'role':
                $qb->orderBy('r.role', $order);
                break;
        }

        // Compte total des résultats
        $countQb = clone $qb;
        $countQb->select('COUNT(DISTINCT p.id)');
        $total = (int) $countQb->getQuery()->getSingleScalarResult();

        // Récupération des résultats paginés
        $qb->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        return [
            'items' => array_column($qb->getQuery()->getResult(), 'id'),
            'total' => $total,
        ];
    }

    public function findFilteredVisitorPersonIdsPaginated(
        int     $page = 1,
        int     $limit = 50,
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
        $order = $order ?? 'ASC';

        $qb = $this->createQueryBuilder('p')
            ->select('DISTINCT p.id')
            ->innerJoin('p.visitors', 'v')
            ->innerJoin('v.roundDetail', 'rd')
            ->innerJoin('rd.round', 'r');

        if ($eventId !== null) {
            $qb->andWhere('r.event = :eventId')
                ->setParameter('eventId', $eventId);
        }
        if ($roundId !== null) {
            $qb->andWhere('rd.round = :roundId')
                ->setParameter('roundId', $roundId);
        }
        if ($roundDetailId !== null) {
            $qb->andWhere('v.roundDetail = :roundDetailId')
                ->setParameter('roundDetailId', $roundDetailId);
        }
        if ($name !== null) {
            $qb->andWhere('p.firstName LIKE :name OR p.lastName LIKE :name')
                ->setParameter('name', '%' . $name . '%');
        }
        if ($email !== null) {
            $qb->andWhere('p.email LIKE :email')
                ->setParameter('email', '%' . $email . '%');
        }
        if ($phone !== null) {
            $qb->andWhere('p.phone LIKE :phone')
                ->setParameter('phone', '%' . $phone . '%');
        }
        if ($fromCompanions !== null) {
            $qb->andWhere('v.companions >= :fromCompanions')
                ->setParameter('fromCompanions', $fromCompanions);
        }
        if ($toCompanions !== null) {
            $qb->andWhere('v.companions <= :toCompanions')
                ->setParameter('toCompanions', $toCompanions);
        }
        if ($fromDate !== null) {
            $qb->andWhere('v.registrationDate >= :fromDate')
                ->setParameter('fromDate', new \DateTime($fromDate));
        }
        if ($toDate !== null) {
            $qb->andWhere('v.registrationDate <= :toDate')
                ->setParameter('toDate', new \DateTime($toDate));
        }

        switch ($sort) {
            case 'firstName':
                $qb->orderBy('p.firstName', $order);
                break;
            case 'lastName':
                $qb->orderBy('p.lastName', $order);
                break;
            case 'phone':
                $qb->orderBy('p.phone', $order);
                break;
            case 'email':
                $qb->orderBy('p.email', $order);
                break;
            case 'roundDetail':
                $qb->orderBy('v.roundDetail', $order);
                break;
            case 'companions':
                $qb->orderBy('v.companions', $order);
                break;
            case 'registrationDate':
                $qb->orderBy('v.registrationDate', $order);
                break;
            default:
                $qb->orderBy('v.registrationDate', 'DESC');
        }

        // Compte total des résultats
        $countQb = clone $qb;
        $countQb->select('COUNT(DISTINCT p.id)');
        $total = (int) $countQb->getQuery()->getSingleScalarResult();

        // Récupération des résultats paginés
        $qb->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        return [
            'items' => array_column($qb->getQuery()->getResult(), 'id'),
            'total' => $total,
        ];
    }

    public function findPersonsByIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $qb = $this->createQueryBuilder('p')
            ->where('p.id IN (:ids)')
            ->setParameter('ids', $ids);

        $persons = $qb->getQuery()->getResult();

        // Créer un tableau associatif pour un accès rapide par ID
        $personsById = [];
        foreach ($persons as $person) {
            $personsById[$person->getId()] = $person;
        }

        // Réorganiser selon l'ordre des IDs fournis
        $orderedPersons = [];
        foreach ($ids as $id) {
            if (isset($personsById[$id])) {
                $orderedPersons[] = $personsById[$id];
            }
        }

        return $orderedPersons;
    }


    private function normalize(string $str): string
    {
        $normalized = strtolower(trim($str));
        return preg_replace('/\s+/', ' ', $normalized);
    }
}
