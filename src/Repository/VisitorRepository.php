<?php

namespace App\Repository;

use App\Entity\Visitor;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Visitor>
 */
class VisitorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Visitor::class);
    }

    public function findVisitorsPaginated(
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
    ): QueryBuilder
    {
        $order = $order ?? 'ASC';

        $qb = $this->createQueryBuilder('v')
            ->innerJoin('v.roundDetail', 'rd')
            ->innerJoin('v.person', 'p');

        if ($eventId !== null) {
            $qb->innerJoin('rd.round', 'r')
                ->andWhere('r.event = :eventId')
                ->setParameter('eventId', $eventId);
        }
        if ($roundId !== null) {
            $qb->andWhere('rd.round = :roundId')
                ->setParameter('roundId', $roundId);
        }
        if ($roundDetailId !== null) {
            $qb->andWhere('v.roundDetail.id = :roundDetailId')
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
            case 'companions':
                $qb->orderBy('v.companions', $order);
                break;
            case 'fromDate':
                $qb->orderBy('v.registrationDate', $order);
                break;
        }

        return $qb;
    }

    //    /**
    //     * @return Visitor[] Returns an array of Visitor objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('v')
    //            ->andWhere('v.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('v.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Visitor
    //    {
    //        return $this->createQueryBuilder('v')
    //            ->andWhere('v.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
