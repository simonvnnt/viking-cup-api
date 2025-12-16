<?php

namespace App\Entity;

use App\Repository\TicketRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TicketRepository::class)]
class Ticket
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $externalId = null;

    #[ORM\Column(length: 255)]
    private ?string $ticketNumber = null;

    #[ORM\Column(length: 255)]
    private ?string $barcode = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $creationDate = null;

    #[ORM\Column(length: 255)]
    private ?string $ticketLabel = null;

    #[ORM\ManyToOne(inversedBy: 'tickets')]
    private ?Category $category = null;

    /**
     * @var Collection<int, RoundDetail>
     */
    #[ORM\ManyToMany(targetEntity: RoundDetail::class, inversedBy: 'tickets')]
    private Collection $roundDetails;

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

    #[ORM\Column(nullable: true)]
    private ?int $pass = null;

    #[ORM\Column]
    private bool $pack = false;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $address = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $city = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $zipCode = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $country = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nationality = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $phone = null;

    /**
     * @var Collection<int, PilotRoundCategory>
     */
    #[ORM\ManyToMany(targetEntity: PilotRoundCategory::class, inversedBy: 'tickets')]
    private Collection $pilotRoundCategory;

    /**
     * @var Collection<int, Visitor>
     */
    #[ORM\ManyToMany(targetEntity: Visitor::class, inversedBy: 'tickets')]
    private Collection $visitors;

    /**
     * @var Collection<int, Round>
     */
    #[ORM\ManyToMany(targetEntity: Round::class, inversedBy: 'tickets')]
    private Collection $rounds;

    public function __construct()
    {
        $this->roundDetails = new ArrayCollection();
        $this->pilotRoundCategory = new ArrayCollection();
        $this->visitors = new ArrayCollection();
        $this->rounds = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getExternalId(): ?string
    {
        return $this->externalId;
    }

    public function setExternalId(?string $externalId): static
    {
        $this->externalId = $externalId;

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

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;

        return $this;
    }

    /**
     * @return Collection<int, RoundDetail>
     */
    public function getRoundDetails(): Collection
    {
        return $this->roundDetails;
    }

    public function addRoundDetail(RoundDetail $roundDetail): static
    {
        if (!$this->roundDetails->contains($roundDetail)) {
            $this->roundDetails->add($roundDetail);
        }

        return $this;
    }

    public function removeRoundDetail(RoundDetail $roundDetail): static
    {
        $this->roundDetails->removeElement($roundDetail);

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

    public function isPack(): bool
    {
        return $this->pack;
    }

    public function setPack(bool $pack): static
    {
        $this->pack = $pack;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): static
    {
        $this->address = $address;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getZipCode(): ?string
    {
        return $this->zipCode;
    }

    public function setZipCode(?string $zipCode): static
    {
        $this->zipCode = $zipCode;

        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): static
    {
        $this->country = $country;

        return $this;
    }

    public function getNationality(): ?string
    {
        return $this->nationality;
    }

    public function setNationality(?string $nationality): static
    {
        $this->nationality = $nationality;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    /**
     * @return Collection<int, PilotRoundCategory>
     */
    public function getPilotRoundCategory(): Collection
    {
        return $this->pilotRoundCategory;
    }

    public function addPilotRoundCategory(PilotRoundCategory $driverRoundCategory): static
    {
        if (!$this->pilotRoundCategory->contains($driverRoundCategory)) {
            $this->pilotRoundCategory->add($driverRoundCategory);
        }

        return $this;
    }

    public function removePilotRoundCategory(PilotRoundCategory $driverRoundCategory): static
    {
        $this->pilotRoundCategory->removeElement($driverRoundCategory);

        return $this;
    }

    /**
     * @return Collection<int, Visitor>
     */
    public function getVisitors(): Collection
    {
        return $this->visitors;
    }

    public function addVisitor(Visitor $visitor): static
    {
        if (!$this->visitors->contains($visitor)) {
            $this->visitors->add($visitor);
        }

        return $this;
    }

    public function removeVisitor(Visitor $visitor): static
    {
        $this->visitors->removeElement($visitor);

        return $this;
    }

    /**
     * @return Collection<int, Round>
     */
    public function getRounds(): Collection
    {
        return $this->rounds;
    }

    public function addRound(Round $round): static
    {
        if (!$this->rounds->contains($round)) {
            $this->rounds->add($round);
        }

        return $this;
    }

    public function removeRound(Round $round): static
    {
        $this->rounds->removeElement($round);

        return $this;
    }
}
