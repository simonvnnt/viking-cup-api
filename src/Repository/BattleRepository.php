<?php

namespace App\Repository;

use App\Entity\Battle;
use App\Entity\Category;
use App\Entity\PilotRoundCategory;
use App\Entity\Round;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Battle>
 */
class BattleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Battle::class);
    }

    /**
     * @param Round $round
     * @param Category $category
     * @param int|null $passage
     * @return Battle[]
     */
    public function getBattleVersus(Round $round, Category $category, ?int $passage = null): array
    {
        $qb = $this->createQueryBuilder('b')
            ->leftJoin('b.leader', 'prc1')
            ->leftJoin('b.chaser', 'prc2')
            ->andWhere('prc1 IS NULL OR prc1.round = :round')
            ->andWhere('prc1 IS NULL OR prc1.category = :category')
            ->andWhere('prc2 IS NULL OR prc2.round = :round')
            ->andWhere('prc2 IS NULL OR prc2.category = :category')
            ->setParameter('round', $round)
            ->setParameter('category', $category);

        if ($passage !== null) {
            $qb->andWhere('b.passage = :passage')
                ->setParameter('passage', $passage);
        }

        return $qb->getQuery()
            ->getResult();
    }

    public function getBattleRanking(Round $round, Category $category)
    {
        return $this->createQueryBuilder('b')
            ->select([
                'prc',
                'SUM(CASE WHEN b.winner = prc THEN 1 ELSE 0 END) AS wins',
                'MAX(CASE WHEN b.winner != prc THEN b.passage ELSE 0 END) AS last_defeat_passage'
            ])
            ->leftJoin('b.leader', 'prc1')
            ->leftJoin('b.chaser', 'prc2')
            ->innerJoin(PilotRoundCategory::class, 'prc', Join::WITH, 'b.leader = prc OR b.chaser = prc')
            ->andWhere('prc1 IS NULL OR (prc1.round = :round AND prc1.isEngaged = :isEngaged)')
            ->andWhere('prc1 IS NULL OR (prc1.category = :category AND prc1.isEngaged = :isEngaged)')
            ->andWhere('prc2 IS NULL OR (prc2.round = :round AND prc2.isEngaged = :isEngaged)')
            ->andWhere('prc2 IS NULL OR (prc2.category = :category AND prc2.isEngaged = :isEngaged)')
            ->setParameter('round', $round)
            ->setParameter('category', $category)
            ->setParameter('isEngaged', true)
            ->groupBy('prc')
            ->orderBy('wins', 'DESC')
            ->addOrderBy('last_defeat_passage', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getMaxPassage(Round $round, Category $category)
    {
        return $this->createQueryBuilder('b')
            ->select('MAX(b.passage)')
            ->leftJoin('b.leader', 'prc1')
            ->leftJoin('b.chaser', 'prc2')
            ->andWhere('prc1 IS NULL OR (prc1.round = :round AND prc1.isEngaged = :isEngaged)')
            ->andWhere('prc1 IS NULL OR (prc1.category = :category AND prc1.isEngaged = :isEngaged)')
            ->andWhere('prc2 IS NULL OR (prc2.round = :round AND prc2.isEngaged = :isEngaged)')
            ->andWhere('prc2 IS NULL OR (prc2.category = :category AND prc2.isEngaged = :isEngaged)')
            ->setParameter('round', $round)
            ->setParameter('category', $category)
            ->setParameter('isEngaged', true)
            ->getQuery()
            ->getSingleScalarResult();
    }

    //    /**
    //     * @return Battle[] Returns an array of Battle objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('b')
    //            ->andWhere('b.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('b.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Battle
    //    {
    //        return $this->createQueryBuilder('b')
    //            ->andWhere('b.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
