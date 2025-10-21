<?php

namespace App\Business;

use App\Dto\AccountingDto;
use App\Entity\Accounting;
use App\Enum\IterationType;
use App\Helper\FileHelper;
use App\Repository\AccountingRepository;
use App\Repository\AccountingTypeRepository;
use App\Repository\EventRepository;
use App\Repository\RoundRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

readonly class AccountingBusiness
{
    public function __construct(
        private AccountingRepository   $accountingRepository,
        private AccountingTypeRepository $accountingTypeRepository,
        private RoundRepository $roundRepository,
        private EventRepository $eventRepository,
        private FileHelper $fileHelper,
        private EntityManagerInterface $em
    )
    {}

    public function getAccountings(
        int $page,
        int $limit,
        ?string $sort = null,
        ?string $order = null,
        ?int    $eventId = null,
        ?int    $roundId = null,
        ?string $name = null,
        ?string $iteration = null,
        ?int    $accountingTypeId = null,
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
        $accountingsPaginated = $this->accountingRepository->findPaginated(
            $page,
            $limit,
            $sort,
            $order,
            $eventId,
            $roundId,
            $name,
            $iteration,
            $accountingTypeId,
            $minUnitPrice,
            $maxUnitPrice,
            $minQuantity,
            $maxQuantity,
            $fromDate,
            $toDate,
            $isDone,
            $isConfirmed
        );

        return [
            'pagination' => [
                'totalItems' => $accountingsPaginated['total'],
                'pageIndex' => $page,
                'itemsPerPage' => $limit
            ],
            'accountings' => $accountingsPaginated['items']
        ];
    }

    public function createAccounting(AccountingDto $accountingDto, ?UploadedFile $invoiceFile): ?Accounting
    {
        $accounting = new Accounting();
        $accounting->setName($accountingDto->name)
            ->setIteration($accountingDto->iteration !== null ? IterationType::from($accountingDto->iteration) : null)
            ->setUnitPrice($accountingDto->unitPrice ?? 0.0)
            ->setQuantity($accountingDto->quantity ?? 0)
            ->setDate($accountingDto->date !== null ? new \DateTimeImmutable($accountingDto->date) : new \DateTimeImmutable())
            ->setIsDone($accountingDto->isDone ?? false)
            ->setIsConfirmed($accountingDto->isConfirmed ?? false)
            ->setComment($accountingDto->comment);

        if ($accountingDto->roundId !== null) {
            $round = $this->roundRepository->find($accountingDto->roundId);
            if ($round) {
                $accounting->setRound($round);
            }
        }

        if ($accountingDto->eventId !== null) {
            $event = $this->eventRepository->find($accountingDto->eventId);
            if ($event) {
                $accounting->setEvent($event);
            }
        }

        if ($accountingDto->accountingTypeId !== null) {
            $accountingType = $this->accountingTypeRepository->find($accountingDto->accountingTypeId);
            if ($accountingType) {
                $accounting->setAccountingType($accountingType);
            }
        }

        $this->em->persist($accounting);
        $this->em->flush();

        if ($invoiceFile !== null) {
            $fileName = 'invoice_' . $accounting->getId() . '.' . $invoiceFile->getClientOriginalExtension();
            $savedFile = $this->fileHelper->saveFile($invoiceFile, 'uploads/invoices', $fileName);
            $accounting->setInvoicePath($savedFile->getPathname());

            $this->em->persist($accounting);
            $this->em->flush();
        }

        return $accounting;
    }

    public function updateAccounting(Accounting $accounting, AccountingDto $accountingDto, ?UploadedFile $invoiceFile): void
    {
        $accounting->setName($accountingDto->name)
            ->setIteration($accountingDto->iteration !== null ? IterationType::from($accountingDto->iteration) : null)
            ->setUnitPrice($accountingDto->unitPrice ?? 0.0)
            ->setQuantity($accountingDto->quantity ?? 0)
            ->setDate($accountingDto->date !== null ? new \DateTimeImmutable($accountingDto->date) : $accounting->getDate())
            ->setIsDone($accountingDto->isDone ?? false)
            ->setIsConfirmed($accountingDto->isConfirmed ?? false)
            ->setComment($accountingDto->comment);

        if ($accountingDto->roundId !== null) {
            $round = $this->roundRepository->find($accountingDto->roundId);
            if ($round) {
                $accounting->setRound($round);
            }
        }

        if ($accountingDto->eventId !== null) {
            $event = $this->eventRepository->find($accountingDto->eventId);
            if ($event) {
                $accounting->setEvent($event);
            }
        }

        if ($accountingDto->accountingTypeId !== null) {
            $accountingType = $this->accountingTypeRepository->find($accountingDto->accountingTypeId);
            if ($accountingType) {
                $accounting->setAccountingType($accountingType);
            }
        }

        if ($invoiceFile !== null) {
            // Delete old invoice file if exists
            if ($accounting->getInvoicePath() !== null) {
                $this->fileHelper->deleteFile($accounting->getInvoicePath());
            }

            $fileName = 'invoice_' . $accounting->getId() . '.' . $invoiceFile->getClientOriginalExtension();
            $savedFile = $this->fileHelper->saveFile($invoiceFile, 'uploads/invoices', $fileName);
            $accounting->setInvoicePath($savedFile->getPathname());
        }

        $this->em->persist($accounting);
        $this->em->flush();
    }

    public function deleteAccountingInvoice(Accounting $accounting): Accounting
    {
        if ($accounting->getInvoicePath() !== null) {
            $this->fileHelper->deleteFile($accounting->getInvoicePath());
            $accounting->setInvoicePath(null);

            $this->em->persist($accounting);
            $this->em->flush();
        }

        return $accounting;
    }

    public function deleteAccounting(Accounting $accounting): void
    {
        $this->em->remove($accounting);
        $this->em->flush();
    }
}