<?php

namespace App\Repository;

use App\Entity\Media;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Media>
 */
class MediaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Media::class);
    }

    public function countMedias(?int $eventId, ?int $roundId): int
    {
        $qb = $this->createQueryBuilder('m')
            ->select('COUNT(DISTINCT m.id)')
            ->andWhere('m.selected = 1');

        if ($roundId !== null) {
            $qb->andWhere('m.round = :roundId')
                ->setParameter('roundId', $roundId);
        } elseif ($eventId !== null) {
            $qb->innerJoin('m.round', 'r')
                ->andWhere('r.event = :eventId')
                ->setParameter('eventId', $eventId);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
