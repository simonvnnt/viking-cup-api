<?php

namespace App\Controller\Api;

use App\Business\AccountingCategoryBusiness;
use App\Dto\AccountingCategoryDto;
use App\Entity\AccountingCategory;
use App\Repository\AccountingCategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/accounting-categories', name: 'api_accounting_categories')]
class AccountingCategoryApiController extends AbstractController
{

    #[Route('', name: 'list', methods: ['GET'])]
    public function getAccountingCategories(
        AccountingCategoryRepository $accountingCategoryRepository
    ): JsonResponse
    {
        $accountingCategories = $accountingCategoryRepository->findAll();

        return $this->json($accountingCategories, Response::HTTP_OK, [], ['groups' => ['accountingCategory']]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function createAccountingCategory(
        AccountingCategoryBusiness $accountingCategoryBusiness,
        #[MapRequestPayload] AccountingCategoryDto $accountingCategoryDto
    ): Response
    {
        $accountingCategory = $accountingCategoryBusiness->createAccountingCategory($accountingCategoryDto);

        return $this->json($accountingCategory, Response::HTTP_OK, [], ['groups' => ['accountingCategory']]);
    }

    #[Route('/{accountingCategory}', name: 'delete', methods: ['DELETE'])]
    public function deleteAccountingCategory(
        AccountingCategoryBusiness $accountingCategoryBusiness,
        AccountingCategory $accountingCategory
    ): Response
    {
        $accountingCategoryBusiness->deleteAccountingCategory($accountingCategory);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}