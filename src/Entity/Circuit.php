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
    private ?string $text = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['circuit'])]
    private ?string $imagePath = null;

    /**
     * @var Collection<int, Round>
     */
    #[ORM\OneToMany(targetEntity: Round::class, mappedBy: 'circuit')]
    #[Groups(['circuitRound'])]
    private Collection $rounds;

    public function __construct()
    {
        $this->rounds = new ArrayCollection();
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

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(?string $text): static
    {
        $this->text = $text;

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
}
