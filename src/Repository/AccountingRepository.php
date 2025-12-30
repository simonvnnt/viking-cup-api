<?php

namespace App\Repository;

use App\Entity\Accounting;
use App\Entity\Event;
use App\Entity\Round;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Accounting>
 */
class AccountingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Accounting::class);
    }

    public function findPaginated(
        int     $page = 1,
        int     $limit = 50,
        ?string $sort = null,
        ?string $order = null,
        ?string $accountingType = null,
        ?int    $eventId = null,
        ?int    $roundId = null,
        ?string $name = null,
        ?string $iteration = null,
        ?int    $accountingCategoryId = null,
        ?float  $minUnitPrice = null,
        ?float  $maxUnitPrice = null,
        ?int    $minQuantity = null,
        ?int    $maxQuantity = null,
        ?string $fromDate = null,
        ?string $toDate = null,
        ?bool   $isDone = null,
        ?bool   $isConfirmed = null
    ): array
    {
        $order = $order ?? 'ASC';

        $qb = $this->createQueryBuilder('a');

        if ($accountingType !== null) {
            $qb->andWhere('a.accountingType = :accountingType')
                ->setParameter('accountingType', $accountingType);
        }
        if ($eventId !== null) {
            $qb->andWhere('a.event = :eventId')
                ->setParameter('eventId', $eventId);
        }
        if ($roundId !== null) {
            $qb->andWhere('a.round = :roundId')
                ->setParameter('roundId', $roundId);
        }
        if ($name !== null) {
            $qb->andWhere('a.name LIKE :name')
                ->setParameter('name', '%' . $name . '%');
        }
        if ($iteration !== null) {
            $qb->andWhere('a.iteration = :iteration')
                ->setParameter('iteration', $iteration);
        }
        if ($accountingCategoryId !== null) {
            $qb->andWhere('a.accountingCategory = :accountingCategoryId')
                ->setParameter('accountingCategoryId', $accountingCategoryId);
        }
        if ($minUnitPrice !== null) {
            $qb->andWhere('a.unitPrice >= :minUnitPrice')
                ->setParameter('minUnitPrice', $minUnitPrice);
        }
        if ($maxUnitPrice !== null) {
            $qb->andWhere('a.unitPrice <= :maxUnitPrice')
                ->setParameter('maxUnitPrice', $maxUnitPrice);
        }
        if ($minQuantity !== null) {
            $qb->andWhere('a.quantity >= :minQuantity')
                ->setParameter('minQuantity', $minQuantity);
        }
        if ($maxQuantity !== null) {
            $qb->andWhere('a.quantity <= :maxQuantity')
                ->setParameter('maxQuantity', $maxQuantity);
        }
        if ($fromDate !== null) {
            $qb->andWhere('a.date >= :fromDate')
                ->setParameter('fromDate', new \DateTime($fromDate));
        }
        if ($toDate !== null) {
            $qb->andWhere('a.date <= :toDate')
                ->setParameter('toDate', new \DateTime($toDate));
        }
        if ($isDone !== null) {
            $qb->andWhere('a.isDone = :isDone')
                ->setParameter('isDone', $isDone);
        }
        if ($isConfirmed !== null) {
            $qb->andWhere('a.isConfirmed = :isConfirmed')
                ->setParameter('isConfirmed', $isConfirmed);
        }

        if ($sort) {
            $qb->orderBy('a.' . $sort, $order);
        } else {
            $qb->orderBy('a.date', 'DESC');
        }

        // Compte total des résultats
        $countQb = clone $qb;
        $countQb->select('COUNT(DISTINCT a.id)');
        $total = (int)$countQb->getQuery()->getSingleScalarResult();

        // Récupération des résultats paginés
        $qb->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        return [
            'items' => $qb->getQuery()->getResult(),
            'total' => $total,
        ];
    }

    /**
     * @return Accounting[]
     */
    public function findByEventAndRound(string $type, ?Event $event, ?Round $round): array
    {
        $qb = $this->createQueryBuilder('a')
            ->andWhere('a.isDone = 1')
            ->andWhere('a.isConfirmed = 1')
            ->andWhere('a.accountingType = :type')
            ->setParameter('type', $type);

        if ($round !== null) {
            $qb->andWhere('a.round = :round OR a.event = :event')
                ->setParameter('round', $round)
                ->setParameter('event', $round->getEvent());
        } else if ($event !== null) {
            $qb->leftJoin('a.round', 'r')
                ->andWhere('a.event = :event OR r.event = :event')
                ->setParameter('event', $event);
        }

        return $qb->getQuery()->getResult();
    }

//    /**
//     * @return Accounting[] Returns an array of Accounting objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('a.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Accounting
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
