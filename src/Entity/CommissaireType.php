<?php

namespace App\Entity;

use App\Repository\CommissaireTypeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: CommissaireTypeRepository::class)]
class CommissaireType
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups('commissaireType')]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups('commissaireType')]
    private ?string $name = null;

    /**
     * @var Collection<int, Commissaire>
     */
    #[ORM\OneToMany(targetEntity: Commissaire::class, mappedBy: 'commissaireType')]
    private Collection $commissaires;

    public function __construct()
    {
        $this->commissaires = new ArrayCollection();
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
     * @return Collection<int, Commissaire>
     */
    public function getCommissaires(): Collection
    {
        return $this->commissaires;
    }

    public function addCommissaire(Commissaire $commissaire): static
    {
        if (!$this->commissaires->contains($commissaire)) {
            $this->commissaires->add($commissaire);
            $commissaire->setType($this);
        }

        return $this;
    }

    public function removeCommissaire(Commissaire $commissaire): static
    {
        if ($this->commissaires->removeElement($commissaire)) {
            // set the owning side to null (unless already changed)
            if ($commissaire->getType() === $this) {
                $commissaire->setType(null);
            }
        }

        return $this;
    }
}
