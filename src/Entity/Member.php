<?php

namespace App\Entity;

use App\Repository\MemberRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: MemberRepository::class)]
#[ORM\Table(name: '`member`')]
class Member
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups('member')]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'member', cascade: ['persist', 'remove'])]
    #[Groups('memberPerson')]
    private ?Person $person = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups('member')]
    private ?string $roleAsso = null;

    #[ORM\Column(length: 255)]
    #[Groups('member')]
    private ?string $roleVcup = null;

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

    public function getRoleAsso(): ?string
    {
        return $this->roleAsso;
    }

    public function setRoleAsso(?string $roleAsso): static
    {
        $this->roleAsso = $roleAsso;

        return $this;
    }

    public function getRoleVcup(): ?string
    {
        return $this->roleVcup;
    }

    public function setRoleVcup(string $roleVcup): static
    {
        $this->roleVcup = $roleVcup;

        return $this;
    }
}
