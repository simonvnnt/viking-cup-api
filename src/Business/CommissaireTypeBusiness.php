<?php

namespace App\Business;

use App\Dto\CommissaireTypeDto;
use App\Entity\CommissaireType;
use App\Repository\CommissaireTypeRepository;
use Doctrine\ORM\EntityManagerInterface;

readonly class CommissaireTypeBusiness
{
    public function __construct(
        private CommissaireTypeRepository $commissaireTypeRepository,
        private EntityManagerInterface $em
    )
    {}

    public function createCommissaireType(CommissaireTypeDto $commissaireTypeDto): CommissaireType
    {
        $commissaireType = $this->commissaireTypeRepository->findBy(['name' => $commissaireTypeDto->name], [], 1)[0] ?? null;

        if ($commissaireType === null) {
            $commissaireType = new CommissaireType();
            $commissaireType->setName($commissaireTypeDto->name);
            $this->em->persist($commissaireType);
        }

        $this->em->flush();

        return $commissaireType;
    }

    public function deleteCommissaireType(CommissaireType $commissaireType): void
    {
        $this->em->remove($commissaireType);
        $this->em->flush();
    }
}