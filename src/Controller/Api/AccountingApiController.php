<?php

namespace App\Controller\Api;

use App\Business\AccountingBusiness;
use App\Dto\AccountingDto;
use App\Entity\Accounting;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Attribute\MapUploadedFile;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/accounting', name: 'api_accounting')]
class AccountingApiController extends AbstractController
{
    #[Route('', name: 'list', methods: ['GET'])]
    public function getAccountings(
        AccountingBusiness $accountingBusiness,
        #[MapQueryParameter] ?int $page,
        #[MapQueryParameter] ?int $limit,
        #[MapQueryParameter] ?string $sort,
        #[MapQueryParameter] ?string $order,
        #[MapQueryParameter] ?string $accountingType = null,
        #[MapQueryParameter] ?int    $eventId = null,
        #[MapQueryParameter] ?int    $roundId = null,
        #[MapQueryParameter] ?string $name = null,
        #[MapQueryParameter] ?string $iteration = null,
        #[MapQueryParameter] ?int    $accountingCategoryId = null,
        #[MapQueryParameter] ?float  $minUnitPrice = null,
        #[MapQueryParameter] ?float  $maxUnitPrice = null,
        #[MapQueryParameter] ?int    $minQuantity = null,
        #[MapQueryParameter] ?int    $maxQuantity = null,
        #[MapQueryParameter] ?string $fromDate = null,
        #[MapQueryParameter] ?string $toDate = null,
        #[MapQueryParameter] ?bool   $isDone = null,
        #[MapQueryParameter] ?bool   $isConfirmed = null,
    ): JsonResponse
    {
        $accountings = $accountingBusiness->getAccountings(
            $page ?? 1,
            $limit ?? 50,
            $sort,
            $order,
            $accountingType,
            $eventId,
            $roundId,
            $name,
            $iteration,
            $accountingCategoryId,
            $minUnitPrice,
            $maxUnitPrice,
            $minQuantity,
            $maxQuantity,
            $fromDate,
            $toDate,
            $isDone,
            $isConfirmed
        );

        return $this->json($accountings, Response::HTTP_OK, [], ['groups' => ['accounting', 'accountingAccountingCategory', 'accountingCategory', 'accountingRound', 'round', 'roundEvent', 'accountingEvent', 'event']]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function createAccounting(
        AccountingBusiness $accountingBusiness,
        Request $request,
        SerializerInterface $serializer,
        #[MapUploadedFile] UploadedFile|array $invoiceFile = []
    ): Response
    {
        $accountingDto = $request->request->get('accounting');
        $accountingDto = $serializer->deserialize($accountingDto, AccountingDto::class, 'json');

        $accounting = $accountingBusiness->createAccounting($accountingDto, !empty($invoiceFile) ? $invoiceFile : null);

        return $this->json($accounting, Response::HTTP_CREATED, [], ['groups' => ['accounting', 'accountingAccountingCategory', 'accountingCategory']]);
    }

    #[Route('/{accounting}', name: 'update', methods: ['POST'])]
    public function updateAccounting(
        AccountingBusiness $accountingBusiness,
        Accounting $accounting,
        Request $request,
        SerializerInterface $serializer,
        #[MapUploadedFile] UploadedFile|array $invoiceFile = []
    ): Response
    {
        $accountingDto = $request->request->get('accounting');
        $accountingDto = $serializer->deserialize($accountingDto, AccountingDto::class, 'json');

        $accountingBusiness->updateAccounting($accounting, $accountingDto, !empty($invoiceFile) ? $invoiceFile : null);

        return new Response();
    }

    #[Route('/invoice/{accounting}', name: 'delete_invoice', methods: ['DELETE'])]
    public function deleteMediaBook(
        AccountingBusiness $accountingBusiness,
        Accounting $accounting
    ): Response
    {
        $accounting = $accountingBusiness->deleteAccountingInvoice($accounting);

        return $this->json($accounting, Response::HTTP_OK, [], ['groups' => ['accounting', 'accountingAccountingCategory', 'accountingCategory']]);
    }

    #[Route('/{accounting}', name: 'delete', methods: ['DELETE'])]
    public function deleteAccounting(
        AccountingBusiness $accountingBusiness,
        Accounting $accounting
    ): Response
    {
        $accountingBusiness->deleteAccounting($accounting);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}