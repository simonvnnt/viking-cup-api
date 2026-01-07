<?php

namespace App\Repository;

use App\Entity\Rescuer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Rescuer>
 */
class RescuerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Rescuer::class);
    }

    public function countRescuers(?int $eventId, ?int $roundId): int
    {
        $qb = $this->createQueryBuilder('r')
            ->select('COUNT(DISTINCT r.id)');

        if ($roundId !== null) {
            $qb->andWhere('r.round = :roundId')
                ->setParameter('roundId', $roundId);
        } elseif ($eventId !== null) {
            $qb->innerJoin('r.round', 'round')
                ->andWhere('round.event = :eventId')
                ->setParameter('eventId', $eventId);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    //    /**
    //     * @return Rescuer[] Returns an array of Rescuer objects
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

    //    public function findOneBySomeField($value): ?Rescuer
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
