<?php

namespace App\Business;

use App\Dto\AccountingCategoryDto;
use App\Entity\AccountingCategory;
use App\Repository\AccountingCategoryRepository;
use Doctrine\ORM\EntityManagerInterface;

readonly class AccountingCategoryBusiness
{
    public function __construct(
        private AccountingCategoryRepository $accountingCategoryRepository,
        private EntityManagerInterface       $em
    )
    {}

    public function createAccountingCategory(AccountingCategoryDto $accountingCategoryDto): AccountingCategory
    {
        $accountingCategory = $this->accountingCategoryRepository->findBy(['name' => $accountingCategoryDto->name], [], 1)[0] ?? null;

        if ($accountingCategory === null) {
            $accountingCategory = new AccountingCategory();
            $accountingCategory->setName($accountingCategoryDto->name);
            $this->em->persist($accountingCategory);
        }

        $this->em->flush();

        return $accountingCategory;
    }

    public function deleteAccountingCategory(AccountingCategory $accountingCategory): void
    {
        foreach ($accountingCategory->getAccountings()->toArray() as $accounting) {
            $accounting->setAccountingCategory(null);
            $this->em->persist($accounting);
        }

        $this->em->remove($accountingCategory);
        $this->em->flush();
    }
}