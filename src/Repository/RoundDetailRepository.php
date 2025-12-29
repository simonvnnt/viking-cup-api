<?php

namespace App\Repository;

use App\Entity\RoundDetail;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RoundDetail>
 */
class RoundDetailRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RoundDetail::class);
    }

    public function findByEventAndRound(?int $eventId, ?int $roundId)
    {
        $qb = $this->createQueryBuilder('rd')
            ->innerJoin('rd.round', 'r')
            ->innerJoin('r.event', 'e');

        if ($eventId !== null) {
            $qb->andWhere('e.id = :eventId')
                ->setParameter('eventId', $eventId);
        }

        if ($roundId !== null) {
            $qb->andWhere('r.id = :roundId')
                ->setParameter('roundId', $roundId);
        }

        return $qb->getQuery()->getResult();
    }

    //    /**
    //     * @return RoundDetail[] Returns an array of RoundDetail objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('r.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?RoundDetail
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
