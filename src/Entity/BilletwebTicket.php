<?php

namespace App\Entity;

use App\Repository\BilletwebTicketRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BilletwebTicketRepository::class)]
class BilletwebTicket
{
    #[ORM\Id]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $ticketNumber = null;

    #[ORM\Column(length: 255)]
    private ?string $barcode = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $creationDate = null;

    #[ORM\Column(length: 255)]
    private ?string $ticketLabel = null;

    #[ORM\Column(length: 255)]
    private ?string $category = null;

    #[ORM\Column(length: 255)]
    private ?string $lastName = null;

    #[ORM\Column(length: 255)]
    private ?string $firstName = null;

    #[ORM\Column(length: 255)]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    private ?string $buyerLastName = null;

    #[ORM\Column(length: 255)]
    private ?string $buyerFirstName = null;

    #[ORM\Column(length: 255)]
    private ?string $buyerEmail = null;

    #[ORM\Column(length: 255)]
    private ?string $orderNumber = null;

    #[ORM\Column(length: 255)]
    private ?string $paymentType = null;

    #[ORM\Column]
    private ?float $amount = null;

    #[ORM\Column(nullable: true)]
    private ?float $refundAmount = null;

    #[ORM\Column(nullable: true)]
    private ?float $discountAmount = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $paid = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $used = false;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $usedDate = null;

    #[ORM\Column]
    private ?int $pass = null;

    #[ORM\Column]
    private array $custom = [];

    #[ORM\Column]
    private bool $pack = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getTicketNumber(): ?string
    {
        return $this->ticketNumber;
    }

    public function setTicketNumber(string $ticketNumber): static
    {
        $this->ticketNumber = $ticketNumber;

        return $this;
    }

    public function getBarcode(): ?string
    {
        return $this->barcode;
    }

    public function setBarcode(string $barcode): static
    {
        $this->barcode = $barcode;

        return $this;
    }

    public function getCreationDate(): ?\DateTimeInterface
    {
        return $this->creationDate;
    }

    public function setCreationDate(\DateTimeInterface $creationDate): static
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    public function getTicketLabel(): ?string
    {
        return $this->ticketLabel;
    }

    public function setTicketLabel(string $ticketLabel): static
    {
        $this->ticketLabel = $ticketLabel;

        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(string $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getBuyerLastName(): ?string
    {
        return $this->buyerLastName;
    }

    public function setBuyerLastName(string $buyerLastName): static
    {
        $this->buyerLastName = $buyerLastName;

        return $this;
    }

    public function getBuyerFirstName(): ?string
    {
        return $this->buyerFirstName;
    }

    public function setBuyerFirstName(string $buyerFirstName): static
    {
        $this->buyerFirstName = $buyerFirstName;

        return $this;
    }

    public function getBuyerEmail(): ?string
    {
        return $this->buyerEmail;
    }

    public function setBuyerEmail(string $buyerEmail): static
    {
        $this->buyerEmail = $buyerEmail;

        return $this;
    }

    public function getOrderNumber(): ?string
    {
        return $this->orderNumber;
    }

    public function setOrderNumber(string $orderNumber): static
    {
        $this->orderNumber = $orderNumber;

        return $this;
    }

    public function getPaymentType(): ?string
    {
        return $this->paymentType;
    }

    public function setPaymentType(string $paymentType): static
    {
        $this->paymentType = $paymentType;

        return $this;
    }

    public function getAmount(): ?float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): static
    {
        $this->amount = $amount;

        return $this;
    }

    public function getRefundAmount(): ?float
    {
        return $this->refundAmount;
    }

    public function setRefundAmount(float $refundAmount): static
    {
        $this->refundAmount = $refundAmount;

        return $this;
    }

    public function getDiscountAmount(): ?float
    {
        return $this->discountAmount;
    }

    public function setDiscountAmount(float $discountAmount): static
    {
        $this->discountAmount = $discountAmount;

        return $this;
    }

    public function isPaid(): bool
    {
        return $this->paid;
    }

    public function setPaid(bool $paid): static
    {
        $this->paid = $paid;

        return $this;
    }

    public function isUsed(): bool
    {
        return $this->used;
    }

    public function setUsed(bool $used): static
    {
        $this->used = $used;

        return $this;
    }

    public function getUsedDate(): ?\DateTimeInterface
    {
        return $this->usedDate;
    }

    public function setUsedDate(?\DateTimeInterface $usedDate): static
    {
        $this->usedDate = $usedDate;

        return $this;
    }

    public function getPass(): ?int
    {
        return $this->pass;
    }

    public function setPass(int $pass): static
    {
        $this->pass = $pass;

        return $this;
    }

    public function getCustom(): array
    {
        return $this->custom;
    }

    public function setCustom(array $custom): static
    {
        $this->custom = $custom;

        return $this;
    }

    public function isPack(): bool
    {
        return $this->pack;
    }

    public function setPack(bool $pack): static
    {
        $this->pack = $pack;

        return $this;
    }
}
