<?php

namespace App\Entity;

use App\Enum\IterationType;
use App\Repository\AccountingRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: AccountingRepository::class)]
class Accounting
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups('accounting')]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    #[Groups('accounting')]
    private ?\DateTimeImmutable $date = null;

    #[ORM\Column(length: 255)]
    #[Groups('accounting')]
    private ?string $name = null;

    #[ORM\Column]
    #[Groups('accounting')]
    private ?float $unitPrice = null;

    #[ORM\Column]
    #[Groups('accounting')]
    private ?int $quantity = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups('accounting')]
    private ?string $invoicePath = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups('accounting')]
    private ?string $comment = null;

    #[ORM\Column]
    #[Groups('accounting')]
    private ?bool $isDone = false;

    #[ORM\Column]
    #[Groups('accounting')]
    private ?bool $isConfirmed = false;

    #[ORM\Column(enumType: IterationType::class)]
    #[Groups('accounting')]
    private ?IterationType $iteration = null;

    #[ORM\ManyToOne(inversedBy: 'accountings')]
    #[Groups('accountingAccountingType')]
    private ?AccountingType $accountingType = null;

    #[ORM\ManyToOne(inversedBy: 'accountings')]
    #[Groups('accountingRound')]
    private ?Round $round = null;

    #[ORM\ManyToOne(inversedBy: 'accountings')]
    #[Groups('accountingEvent')]
    private ?Event $event = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDate(): ?\DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(\DateTimeImmutable $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getUnitPrice(): ?float
    {
        return $this->unitPrice;
    }

    public function setUnitPrice(float $unitPrice): static
    {
        $this->unitPrice = $unitPrice;

        return $this;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getInvoicePath(): ?string
    {
        return $this->invoicePath;
    }

    public function setInvoicePath(?string $invoicePath): static
    {
        $this->invoicePath = $invoicePath;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): static
    {
        $this->comment = $comment;

        return $this;
    }

    public function isDone(): ?bool
    {
        return $this->isDone;
    }

    public function setIsDone(bool $isDone): static
    {
        $this->isDone = $isDone;

        return $this;
    }

    public function isConfirmed(): ?bool
    {
        return $this->isConfirmed;
    }

    public function setIsConfirmed(bool $isConfirmed): static
    {
        $this->isConfirmed = $isConfirmed;

        return $this;
    }

    public function getIteration(): ?IterationType
    {
        return $this->iteration;
    }

    public function setIteration(IterationType $iteration): static
    {
        $this->iteration = $iteration;

        return $this;
    }

    public function getAccountingType(): ?AccountingType
    {
        return $this->accountingType;
    }

    public function setAccountingType(?AccountingType $accountingType): static
    {
        $this->accountingType = $accountingType;

        return $this;
    }

    public function getRound(): ?Round
    {
        return $this->round;
    }

    public function setRound(?Round $round): static
    {
        $this->round = $round;

        return $this;
    }

    public function getEvent(): ?Event
    {
        return $this->event;
    }

    public function setEvent(?Event $event): static
    {
        $this->event = $event;

        return $this;
    }
}
