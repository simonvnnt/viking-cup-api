<?php

namespace App\Repository;

use App\Entity\Category;
use App\Entity\PilotRoundCategory;
use App\Entity\Round;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PilotRoundCategory>
 */
class PilotRoundCategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PilotRoundCategory::class);
    }

    /**
     * @return PilotRoundCategory[]
     */
    public function findFilteredPilot(
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

        $qb = $this->createQueryBuilder('prc')
            ->join('prc.pilot', 'pi')
            ->join('pi.person', 'p')
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

        if ($eventId !== null) {
            $qb->innerJoin('prc.round', 'r')
                ->andWhere('r.event = :eventId')
                ->setParameter('eventId', $eventId);
        }
        if ($roundId !== null) {
            $qb->andWhere('prc.round = :roundId')
                ->setParameter('roundId', $roundId);
        }
        if ($categoryId !== null) {
            $qb->andWhere('prc.category = :categoryId')
                ->setParameter('categoryId', $categoryId);
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

        return $qb->getQuery()->getResult();
    }

    public function findWithCorrectPilotEvent(PilotRoundCategory $pilotRoundCategory): ?PilotRoundCategory
    {
        $qb = $this->createQueryBuilder('prc')
            ->select('prc, p, pe')
            ->innerJoin('prc.pilot', 'p')
            ->leftJoin('p.pilotEvents', 'pe', 'WITH', 'pe.event = :event')
            ->andWhere('prc = :pilotRoundCategory')
            ->setParameter('pilotRoundCategory', $pilotRoundCategory)
            ->setParameter('event', $pilotRoundCategory->getRound()->getEvent());

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function findByRoundCategoryQuery(
        Round $round,
        Category $category,
        ?string $sort,
        ?string $order,
        ?string $search = null
    ): Query
    {
        $order = $order ?? 'ASC';

        $qb = $this->createQueryBuilder('prc')
            ->select('prc, p, pe')
            ->innerJoin('prc.pilot', 'p')
            ->innerJoin('p.person', 'person')
            ->leftJoin('p.pilotEvents', 'pe', 'WITH', 'pe.event = :event')
            ->andWhere('prc.round = :round')
            ->andWhere('prc.category = :category')
            ->setParameter('round', $round)
            ->setParameter('category', $category)
            ->setParameter('event', $round->getEvent());

        if ($search !== null) {
            $qb->andWhere('pe.event = :event')
                ->andWhere('
                    person.firstName LIKE :pilot OR
                    person.lastName LIKE :pilot OR
                    CONCAT(person.firstName, \' \', person.lastName) LIKE :pilot OR
                    CONCAT(person.lastName, \' \', person.firstName) LIKE :pilot OR
                    person.email LIKE :pilot OR
                    pe.pilotNumber LIKE :pilot
                ')
                ->setParameter('event', $round->getEvent())
                ->setParameter('pilot', "%$search%");
        }

        switch ($sort) {
            case 'pilotName':
                $qb->addOrderBy('person.lastName', $order);
                break;
            case 'isCompeting':
                $qb->addOrderBy('prc.isCompeting', $order);
                break;
            case 'pilotNumber':
                $qb->addOrderBy('pe.pilotNumber', $order);
                break;
            default:
                $qb->addOrderBy('pe.pilotNumber', $order);
        }

        return $qb->getQuery();
    }
}
