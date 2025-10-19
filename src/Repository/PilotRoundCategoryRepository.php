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
