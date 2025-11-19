<?php

namespace App\Entity;

use App\Repository\CircuitRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: CircuitRepository::class)]
class Circuit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['circuit'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['circuit'])]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['circuit'])]
    private ?string $place = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['circuit'])]
    private ?string $placeImagePath = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['circuit'])]
    private ?string $placeLink = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['circuit'])]
    private ?string $shortDescription = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['circuit'])]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['circuit'])]
    private ?string $imagePath = null;

    /**
     * @var Collection<int, Round>
     */
    #[ORM\OneToMany(targetEntity: Round::class, mappedBy: 'circuit')]
    #[Groups(['circuitRound'])]
    private Collection $rounds;

    /**
     * @var Collection<int, CircuitSpecification>
     */
    #[ORM\OneToMany(targetEntity: CircuitSpecification::class, mappedBy: 'circuit')]
    #[Groups(['circuitCircuitSpecifications'])]
    private Collection $circuitSpecifications;

    public function __construct()
    {
        $this->rounds = new ArrayCollection();
        $this->circuitSpecifications = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getPlace(): ?string
    {
        return $this->place;
    }

    public function setPlace(?string $place): static
    {
        $this->place = $place;

        return $this;
    }

    public function getPlaceImagePath(): ?string
    {
        return $this->placeImagePath;
    }

    public function setPlaceImagePath(?string $placeImagePath): static
    {
        $this->placeImagePath = $placeImagePath;

        return $this;
    }

    public function getPlaceLink(): ?string
    {
        return $this->placeLink;
    }

    public function setPlaceLink(?string $placeLink): static
    {
        $this->placeLink = $placeLink;

        return $this;
    }

    public function getShortDescription(): ?string
    {
        return $this->shortDescription;
    }

    public function setShortDescription(?string $shortDescription): static
    {
        $this->shortDescription = $shortDescription;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getImagePath(): ?string
    {
        return $this->imagePath;
    }

    public function setImagePath(?string $imagePath): static
    {
        $this->imagePath = $imagePath;

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
            $round->setCircuit($this);
        }

        return $this;
    }

    public function removeRound(Round $round): static
    {
        if ($this->rounds->removeElement($round)) {
            // set the owning side to null (unless already changed)
            if ($round->getCircuit() === $this) {
                $round->setCircuit(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, CircuitSpecification>
     */
    public function getCircuitSpecifications(): Collection
    {
        return $this->circuitSpecifications;
    }

    public function addCircuitSpecification(CircuitSpecification $circuitSpecification): static
    {
        if (!$this->circuitSpecifications->contains($circuitSpecification)) {
            $this->circuitSpecifications->add($circuitSpecification);
            $circuitSpecification->setCircuit($this);
        }

        return $this;
    }

    public function removeCircuitSpecification(CircuitSpecification $circuitSpecification): static
    {
        if ($this->circuitSpecifications->removeElement($circuitSpecification)) {
            // set the owning side to null (unless already changed)
            if ($circuitSpecification->getCircuit() === $this) {
                $circuitSpecification->setCircuit(null);
            }
        }

        return $this;
    }
}
