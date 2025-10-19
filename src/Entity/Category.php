<?php

namespace App\Entity;

use App\Repository\CategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: CategoryRepository::class)]
class Category
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['category'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['category'])]
    private ?string $name = null;

    /**
     * @var Collection<int, PilotRoundCategory>
     */
    #[ORM\OneToMany(targetEntity: PilotRoundCategory::class, mappedBy: 'category')]
    private Collection $pilotRoundCategories;

    /**
     * @var Collection<int, RoundCategory>
     */
    #[ORM\OneToMany(targetEntity: RoundCategory::class, mappedBy: 'category')]
    private Collection $roundCategories;

    /**
     * @var Collection<int, PilotNumberCounter>
     */
    #[ORM\OneToMany(targetEntity: PilotNumberCounter::class, mappedBy: 'category')]
    private Collection $pilotNumberCounters;

    public function __construct()
    {
        $this->pilotRoundCategories = new ArrayCollection();
        $this->roundCategories = new ArrayCollection();
        $this->pilotNumberCounters = new ArrayCollection();
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
            $pilotRoundCategory->setCategory($this);
        }

        return $this;
    }

    public function removePilotRoundCategory(PilotRoundCategory $pilotRoundCategory): static
    {
        if ($this->pilotRoundCategories->removeElement($pilotRoundCategory)) {
            // set the owning side to null (unless already changed)
            if ($pilotRoundCategory->getCategory() === $this) {
                $pilotRoundCategory->setCategory(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, RoundCategory>
     */
    public function getRoundCategories(): Collection
    {
        return $this->roundCategories;
    }

    public function addRoundCategory(RoundCategory $roundCategory): static
    {
        if (!$this->roundCategories->contains($roundCategory)) {
            $this->roundCategories->add($roundCategory);
            $roundCategory->setCategory($this);
        }

        return $this;
    }

    public function removeRoundCategory(RoundCategory $roundCategory): static
    {
        if ($this->roundCategories->removeElement($roundCategory)) {
            // set the owning side to null (unless already changed)
            if ($roundCategory->getCategory() === $this) {
                $roundCategory->setCategory(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, PilotNumberCounter>
     */
    public function getPilotNumberCounters(): Collection
    {
        return $this->pilotNumberCounters;
    }

    public function addPilotNumberCounter(PilotNumberCounter $pilotNumberCounter): static
    {
        if (!$this->pilotNumberCounters->contains($pilotNumberCounter)) {
            $this->pilotNumberCounters->add($pilotNumberCounter);
            $pilotNumberCounter->setCategory($this);
        }

        return $this;
    }

    public function removePilotNumberCounter(PilotNumberCounter $pilotNumberCounter): static
    {
        if ($this->pilotNumberCounters->removeElement($pilotNumberCounter)) {
            // set the owning side to null (unless already changed)
            if ($pilotNumberCounter->getCategory() === $this) {
                $pilotNumberCounter->setCategory(null);
            }
        }

        return $this;
    }
}
