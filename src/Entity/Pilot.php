<?php

namespace App\Entity;

use App\Repository\PilotRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: PilotRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Pilot
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['pilot'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'pilots')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['pilotPerson'])]
    private ?Person $person = null;

    #[ORM\ManyToOne(inversedBy: 'pilots')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['pilotEvent'])]
    private ?Event $event = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['pilot'])]
    private ?int $pilotNumber = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['pilot'])]
    private ?bool $ffsaLicensee = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['pilot'])]
    private ?string $ffsaNumber = null;

    #[ORM\Column]
    #[Groups(['pilot'])]
    private ?bool $receiveWindscreenBand = null;

    #[ORM\Column]
    #[Groups(['pilot'])]
    private ?bool $wildCard = false;

    #[ORM\Column(nullable: true)]
    #[Groups(['pilot'])]
    private ?bool $driverLicense = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['pilot'])]
    private ?string $driverLicenseNumber = null;

    /**
     * @var Collection<int, PilotRoundCategory>
     */
    #[ORM\OneToMany(targetEntity: PilotRoundCategory::class, mappedBy: 'pilot', cascade: ['remove'], orphanRemoval: true)]
    #[Groups(['pilotPilotRoundCategories'])]
    private Collection $pilotRoundCategories;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['pilot'])]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->pilotRoundCategories = new ArrayCollection();
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function updatedTimestamps(): void
    {
        $now = new DateTime();
        $this->setUpdatedAt($now);
        if ($this->getCreatedAt() === null) {
            $this->setCreatedAt($now);
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPerson(): ?Person
    {
        return $this->person;
    }

    public function setPerson(?Person $person): static
    {
        $this->person = $person;

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

    public function getPilotNumber(): ?int
    {
        return $this->pilotNumber;
    }

    public function setPilotNumber(?int $pilotNumber): static
    {
        $this->pilotNumber = $pilotNumber;

        return $this;
    }

    public function isFfsaLicensee(): ?bool
    {
        return $this->ffsaLicensee;
    }

    public function setFfsaLicensee(?bool $ffsaLicensee): static
    {
        $this->ffsaLicensee = $ffsaLicensee;

        return $this;
    }

    public function getFfsaNumber(): ?string
    {
        return $this->ffsaNumber;
    }

    public function setFfsaNumber(?string $ffsaNumber): static
    {
        $this->ffsaNumber = $ffsaNumber;

        return $this;
    }

    public function isReceiveWindscreenBand(): ?bool
    {
        return $this->receiveWindscreenBand;
    }

    public function setReceiveWindscreenBand(bool $receiveWindscreenBand): static
    {
        $this->receiveWindscreenBand = $receiveWindscreenBand;

        return $this;
    }

    public function isWildCard(): ?bool
    {
        return $this->wildCard;
    }

    public function setWildCard(bool $wildCard): static
    {
        $this->wildCard = $wildCard;

        return $this;
    }

    /**
     * @return Collection<int, PilotRoundCategory>
     */
    public function getPilotRoundCategories(): Collection
    {
        return $this->pilotRoundCategories;
    }

    public function addPilotRoundCategory(PilotRoundCategory $pilotRoundCategory): static
    {
        if (!$this->pilotRoundCategories->contains($pilotRoundCategory)) {
            $this->pilotRoundCategories->add($pilotRoundCategory);
            $pilotRoundCategory->setPilot($this);
        }

        return $this;
    }

    public function removePilotRoundCategory(PilotRoundCategory $pilotRoundCategory): static
    {
        if ($this->pilotRoundCategories->removeElement($pilotRoundCategory)) {
            // set the owning side to null (unless already changed)
            if ($pilotRoundCategory->getPilot() === $this) {
                $pilotRoundCategory->setPilot(null);
            }
        }

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function isDriverLicense(): ?bool
    {
        return $this->driverLicense;
    }

    public function setDriverLicense(?bool $driverLicense): static
    {
        $this->driverLicense = $driverLicense;

        return $this;
    }

    public function getDriverLicenseNumber(): ?string
    {
        return $this->driverLicenseNumber;
    }

    public function setDriverLicenseNumber(?string $driverLicenseNumber): static
    {
        $this->driverLicenseNumber = $driverLicenseNumber;

        return $this;
    }
}
