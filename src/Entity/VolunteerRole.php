<?php

namespace App\Entity;

use App\Repository\VolunteerRoleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: VolunteerRoleRepository::class)]
class VolunteerRole
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups('volunteerRole')]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups('volunteerRole')]
    private ?string $name = null;

    /**
     * @var Collection<int, Volunteer>
     */
    #[ORM\OneToMany(targetEntity: Volunteer::class, mappedBy: 'role')]
    private Collection $volunteers;

    public function __construct()
    {
        $this->volunteers = new ArrayCollection();
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
     * @return Collection<int, Volunteer>
     */
    public function getVolunteers(): Collection
    {
        return $this->volunteers;
    }

    public function addVolunteer(Volunteer $volunteer): static
    {
        if (!$this->volunteers->contains($volunteer)) {
            $this->volunteers->add($volunteer);
            $volunteer->setRole($this);
        }

        return $this;
    }

    public function removeVolunteer(Volunteer $volunteer): static
    {
        if ($this->volunteers->removeElement($volunteer)) {
            // set the owning side to null (unless already changed)
            if ($volunteer->getRole() === $this) {
                $volunteer->setRole(null);
            }
        }

        return $this;
    }
}
