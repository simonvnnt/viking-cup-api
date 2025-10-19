<?php

namespace App\Repository;

use App\Entity\Pilot;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Pilot>
 */
class PilotRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Pilot::class);
    }

    public function findByName(?string $pilotName): ?Pilot
    {
        $normalized = $this->normalizeName($pilotName);
        $nameParts = explode(' ', $normalized);

        if (empty($nameParts)) {
            return null;
        }

        $qb = $this->createQueryBuilder('p')
            ->innerJoin('p.person', 'person');

        if (count($nameParts) === 1) {
            $qb->where('LOWER(person.firstName) LIKE :name')
                ->orWhere('LOWER(person.lastName) LIKE :name')
                ->setParameter('name', "%$nameParts[0]%");
        } else {
            $conditions = [];

            foreach ($nameParts as $index => $part) {
                $paramKey = "name$index";
                $conditions[] = "(LOWER(person.firstName) LIKE :$paramKey OR LOWER(person.lastName) LIKE :$paramKey)";
                $qb->setParameter($paramKey, "%$part%");
            }

            $qb->where(implode(' AND ', $conditions));
        }

        $qb->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    private function normalizeName(string $name): string
    {
        $normalized = strtolower(trim($name));
        return preg_replace('/\s+/', ' ', $normalized);
    }
}
