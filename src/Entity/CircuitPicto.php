<?php

namespace App\Entity;

use App\Enum\CircuitPictogram;
use App\Repository\CircuitPictoRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: CircuitPictoRepository::class)]
class CircuitPicto
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups('circuitPicto')]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'circuitPictos')]
    #[Groups('circuitPictoCircuit')]
    private ?Circuit $circuit = null;

    #[ORM\Column(length: 255)]
    #[Groups('circuitPicto')]
    private ?string $title = null;

    #[ORM\Column(enumType: CircuitPictogram::class)]
    #[Groups('circuitPicto')]
    private ?CircuitPictogram $picto = null;

    #[ORM\Column]
    #[Groups('circuitPicto')]
    private ?bool $authorized = true;

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

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getPicto(): ?CircuitPictogram
    {
        return $this->picto;
    }

    public function setPicto(CircuitPictogram $picto): static
    {
        $this->picto = $picto;

        return $this;
    }

    public function isAuthorized(): ?bool
    {
        return $this->authorized;
    }

    public function setAuthorized(bool $authorized): static
    {
        $this->authorized = $authorized;

        return $this;
    }
}
