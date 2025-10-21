<?php

namespace App\Entity;

use App\Repository\AccountingTypeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: AccountingTypeRepository::class)]
class AccountingType
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups('accountingType')]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups('accountingType')]
    private ?string $name = null;

    /**
     * @var Collection<int, Accounting>
     */
    #[ORM\OneToMany(targetEntity: Accounting::class, mappedBy: 'accountingType')]
    #[Groups('accountingTypeAccountings')]
    private Collection $accountings;

    public function __construct()
    {
        $this->accountings = new ArrayCollection();
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
     * @return Collection<int, Accounting>
     */
    public function getAccountings(): Collection
    {
        return $this->accountings;
    }

    public function addAccounting(Accounting $accounting): static
    {
        if (!$this->accountings->contains($accounting)) {
            $this->accountings->add($accounting);
            $accounting->setAccountingType($this);
        }

        return $this;
    }

    public function removeAccounting(Accounting $accounting): static
    {
        if ($this->accountings->removeElement($accounting)) {
            // set the owning side to null (unless already changed)
            if ($accounting->getAccountingType() === $this) {
                $accounting->setAccountingType(null);
            }
        }

        return $this;
    }
}
