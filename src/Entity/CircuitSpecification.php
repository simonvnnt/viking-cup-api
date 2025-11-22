<?php

namespace App\Entity;

use App\Repository\CircuitSpecificationRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: CircuitSpecificationRepository::class)]
class CircuitSpecification
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['circuitSpecification'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'circuitSpecifications')]
    private ?Circuit $circuit = null;

    #[ORM\Column(length: 255)]
    #[Groups(['circuitSpecification'])]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['circuitSpecification'])]
    private ?string $value = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCircuit(): ?Circuit
    {
        return $this->circuit;
    }

    public function setCircuit(?Circuit $circuit): static
    {
        $this->circuit = $circuit;

        return $this;
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

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(?string $value): static
    {
        $this->value = $value;

        return $this;
    }
}
