<?php

namespace App\Entity;

use App\Repository\VehicleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: VehicleRepository::class)]
class Vehicle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['vehicle'])]
    private ?int $id = null;

    /**
     * @var Collection<int, PilotRoundCategory>
     */
    #[ORM\OneToMany(targetEntity: PilotRoundCategory::class, mappedBy: 'vehicle')]
    private Collection $pilotRoundCategories;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $brand = null;

    #[ORM\Column(length: 255)]
    #[Groups(['vehicle'])]
    private ?string $model = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $motorisation = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['vehicle'])]
    private ?int $powerHorse = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $mainPreparation = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $gearboxTransmission = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['vehicle'])]
    private ?bool $rollBars = null;

    #[ORM\Column(nullable: true)]
    private ?bool $fireExtinguisher = null;

    #[ORM\Column(nullable: true)]
    private ?bool $circuitBreaker = null;

    #[ORM\Column(nullable: true)]
    private ?bool $harness = null;

    #[ORM\Column(nullable: true)]
    private ?bool $tub = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $generateOverview = null;

    public function __construct()
    {
        $this->pilotRoundCategories = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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
            $pilotRoundCategory->setVehicle($this);
        }

        return $this;
    }

    public function removePilotRoundCategory(PilotRoundCategory $pilotRoundCategory): static
    {
        if ($this->pilotRoundCategories->removeElement($pilotRoundCategory)) {
            // set the owning side to null (unless already changed)
            if ($pilotRoundCategory->getVehicle() === $this) {
                $pilotRoundCategory->setVehicle(null);
            }
        }

        return $this;
    }

    public function getBrand(): ?string
    {
        return $this->brand;
    }

    public function setBrand(?string $brand): static
    {
        $this->brand = $brand;

        return $this;
    }

    public function getModel(): ?string
    {
        return $this->model;
    }

    public function setModel(string $model): static
    {
        $this->model = $model;

        return $this;
    }

    public function getMotorisation(): ?string
    {
        return $this->motorisation;
    }

    public function setMotorisation(?string $motorisation): static
    {
        $this->motorisation = $motorisation;

        return $this;
    }

    public function getPowerHorse(): ?int
    {
        return $this->powerHorse;
    }

    public function setPowerHorse(?int $powerHorse): static
    {
        $this->powerHorse = $powerHorse;

        return $this;
    }

    public function getMainPreparation(): ?string
    {
        return $this->mainPreparation;
    }

    public function setMainPreparation(?string $mainPreparation): static
    {
        $this->mainPreparation = $mainPreparation;

        return $this;
    }

    public function getGearboxTransmission(): ?string
    {
        return $this->gearboxTransmission;
    }

    public function setGearboxTransmission(?string $gearboxTransmission): static
    {
        $this->gearboxTransmission = $gearboxTransmission;

        return $this;
    }

    public function isRollBars(): ?bool
    {
        return $this->rollBars;
    }

    public function setRollBars(?bool $rollBars): static
    {
        $this->rollBars = $rollBars;

        return $this;
    }

    public function isFireExtinguisher(): ?bool
    {
        return $this->fireExtinguisher;
    }

    public function setFireExtinguisher(?bool $fireExtinguisher): static
    {
        $this->fireExtinguisher = $fireExtinguisher;

        return $this;
    }

    public function isCircuitBreaker(): ?bool
    {
        return $this->circuitBreaker;
    }

    public function setCircuitBreaker(?bool $circuitBreaker): static
    {
        $this->circuitBreaker = $circuitBreaker;

        return $this;
    }

    public function isHarness(): ?bool
    {
        return $this->harness;
    }

    public function setHarness(?bool $harness): static
    {
        $this->harness = $harness;

        return $this;
    }

    public function isTub(): ?bool
    {
        return $this->tub;
    }

    public function setTub(?bool $tub): static
    {
        $this->tub = $tub;

        return $this;
    }

    public function getGenerateOverview(): ?string
    {
        return $this->generateOverview;
    }

    public function setGenerateOverview(?string $generateOverview): static
    {
        $this->generateOverview = $generateOverview;

        return $this;
    }
}
