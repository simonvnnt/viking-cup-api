<?php

namespace App\Repository;

use App\Entity\Event;
use App\Entity\Round;
use App\Entity\Ticket;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Ticket>
 */
class TicketRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ticket::class);
    }

    /**
     * @return Ticket[]
     */
    public function findByEventAndRound(?Event $event, ?Round $round): array
    {
        $qb = $this->createQueryBuilder('t');

        if ($round !== null) {
            $qb->innerJoin('t.rounds', 'r')
                ->andWhere('r.id = :round')
               ->setParameter('round', $round);
        } elseif ($event !== null) {
            $qb->innerJoin('t.rounds', 'r')
               ->andWhere('r.event = :event')
               ->setParameter('event', $event);
        }

        return $qb->getQuery()
            ->getResult();
    }
}
