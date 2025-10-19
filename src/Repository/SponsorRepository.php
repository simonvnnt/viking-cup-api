<?php

namespace App\Repository;

use App\Entity\Sponsor;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Sponsor>
 */
class SponsorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Sponsor::class);
    }

    public function findFilteredSponsorIdsPaginated(
        int     $page = 1,
        int     $limit = 50,
        ?string $sort = null,
        ?string $order = null,
        ?string $name = null,
        ?string $contact = null,
        ?int    $eventId = null,
        ?int    $roundId = null,
        ?string $status = null,
        ?string $counterpartType = null,
        ?int    $minAmount = null,
        ?int    $maxAmount = null,
        ?string $otherCounterpart = null,
        ?bool   $hasContract = null
    ): array
    {
        $order = $order ?? 'ASC';

        $qb = $this->createQueryBuilder('s')
            ->select('DISTINCT s.id');

        if ($name !== null) {
            $qb->andWhere('LOWER(s.name) LIKE :name')
                ->setParameter('name', '%' . $this->normalize($name) . '%');
        }
        if ($contact !== null) {
            $qb->join('s.contact', 'p')
                ->andWhere('LOWER(p.firstName) LIKE :contact OR LOWER(p.lastName) LIKE :contact OR LOWER(p.email) LIKE :contact OR p.phone LIKE :contact')
                ->setParameter('contact', '%' . $this->normalize($contact) . '%');
        }
        if ($eventId !== null || $roundId !== null || $status !== null || $counterpartType !== null || $hasContract !== null) {
            $qb->leftJoin('s.sponsorships', 'ss');

            if ($eventId !== null) {
                $qb->andWhere('ss.event = :eventId')
                    ->setParameter('eventId', $eventId);
            }
            if ($roundId !== null) {
                $qb->andWhere('ss.round = :roundId')
                    ->setParameter('roundId', $roundId);
            }
            if ($status !== null) {
                $qb->andWhere('LOWER(ss.status) = :status')
                    ->setParameter('status', $this->normalize($status));
            }
            if ($counterpartType !== null || $minAmount !== null || $maxAmount !== null || $otherCounterpart !== null) {
                $qb->join('ss.sponsorshipCounterparts', 'sc');

                if ($counterpartType !== null) {
                    $qb->andWhere('LOWER(sc.counterpartType) = :counterpartType')
                        ->setParameter('counterpartType', $this->normalize($counterpartType));
                }
                if ($minAmount !== null) {
                    $qb->andWhere('sc.amount >= :minAmount')
                        ->setParameter('minAmount', $minAmount);
                }
                if ($maxAmount !== null) {
                    $qb->andWhere('sc.amount <= :maxAmount')
                        ->setParameter('maxAmount', $maxAmount);
                }
                if ($otherCounterpart !== null) {
                    $qb->andWhere('LOWER(sc.otherCounterpart) LIKE :otherCounterpart')
                        ->setParameter('otherCounterpart', '%' . $this->normalize($otherCounterpart) . '%');
                }
            }
            if ($hasContract !== null) {
                if ($hasContract) {
                    $qb->andWhere('ss.contractFilePath IS NOT NULL AND ss.contractFilePath != \'\'');
                } else {
                    $qb->andWhere('ss.contractFilePath IS NULL OR ss.contractFilePath = \'\'');
                }
            }
        }

        switch ($sort) {
            case 'name':
                $qb->orderBy('s.name', $order);
                break;
            case 'displayWebsite':
                $qb->orderBy('s.displayWebsite', $order);
                break;
            case 'contact':
                $aliases = $qb->getAllAliases();
                if (!in_array('p', $aliases)) {
                    $qb->join('s.contact', 'p');
                }
                $qb->addOrderBy('p.lastName', $order)
                    ->orderBy('p.firstName', $order);
                break;
            case 'event':
                $aliases = $qb->getAllAliases();
                if (!in_array('ss', $aliases)) {
                    $qb->join('s.sponsorship', 'ss');
                }
                if (!in_array('e', $aliases)) {
                    $qb->leftJoin('ss.event', 'e');
                }
                $qb->orderBy('e.name', $order);
                break;
            case 'round':
                $aliases = $qb->getAllAliases();
                if (!in_array('ss', $aliases)) {
                    $qb->join('s.sponsorship', 'ss');
                }
                if (!in_array('r', $aliases)) {
                    $qb->leftJoin('ss.round', 'r');
                }
                $qb->orderBy('r.name', $order);
                break;
            case 'status':
                $aliases = $qb->getAllAliases();
                if (!in_array('ss', $aliases)) {
                    $qb->join('s.sponsorship', 'ss');
                }
                $qb->orderBy('ss.status', $order);
                break;
            case 'amount':
                $aliases = $qb->getAllAliases();
                if (!in_array('ss', $aliases)) {
                    $qb->join('s.sponsorship', 'ss');
                }
                if (!in_array('sc', $aliases)) {
                    $qb->join('ss.sponsorshipCounterparts', 'sc');
                }
                $qb->orderBy('sc.amount', $order);
                break;
            case 'otherCounterpart':
                $aliases = $qb->getAllAliases();
                if (!in_array('ss', $aliases)) {
                    $qb->join('s.sponsorship', 'ss');
                }
                if (!in_array('sc', $aliases)) {
                    $qb->join('ss.sponsorshipCounterparts', 'sc');
                }
                $qb->orderBy('sc.otherCounterpart', $order);
                break;
            default:
                $qb->orderBy('s.id', $order);
        }

        // Compte total des résultats
        $countQb = clone $qb;
        $countQb->select('COUNT(DISTINCT s.id)');
        $total = (int) $countQb->getQuery()->getSingleScalarResult();

        // Récupération des résultats paginés
        $qb->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        return [
            'items' => array_column($qb->getQuery()->getResult(), 'id'),
            'total' => $total,
        ];
    }

    public function findSponsorsByIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $qb = $this->createQueryBuilder('s')
            ->where('s.id IN (:ids)')
            ->setParameter('ids', $ids);

        $sponsors = $qb->getQuery()->getResult();

        // Créer un tableau associatif pour un accès rapide par ID
        $sponsorsById = [];
        foreach ($sponsors as $sponsor) {
            $sponsorsById[$sponsor->getId()] = $sponsor;
        }

        // Réorganiser selon l'ordre des IDs fournis
        $orderedSponsors = [];
        foreach ($ids as $id) {
            if (isset($sponsorsById[$id])) {
                $orderedSponsors[] = $sponsorsById[$id];
            }
        }

        return $orderedSponsors;
    }

    private function normalize(string $str): string
    {
        $normalized = strtolower(trim($str));
        return preg_replace('/\s+/', ' ', $normalized);
    }

    //    /**
    //     * @return Sponsor[] Returns an array of Sponsor objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('s.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Sponsor
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
