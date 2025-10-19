<?php

namespace App\Entity;

use App\Repository\CommissaireRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: CommissaireRepository::class)]
class Commissaire
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups('commissaire')]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'commissaires')]
    #[Groups('commissairePerson')]
    private ?Person $person = null;

    #[ORM\ManyToOne(inversedBy: 'commissaires')]
    #[Groups('commissaireRound')]
    private ?Round $round = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups('commissaire')]
    private ?string $licenceNumber = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups('commissaire')]
    private ?string $asaCode = null;

    #[ORM\ManyToOne(inversedBy: 'commissaires')]
    #[Groups('type')]
    private ?CommissaireType $type = null;

    #[ORM\Column]
    #[Groups('commissaire')]
    private ?bool $isFlag = false;

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

    public function getRound(): ?Round
    {
        return $this->round;
    }

    public function setRound(?Round $round): static
    {
        $this->round = $round;

        return $this;
    }

    public function getLicenceNumber(): ?string
    {
        return $this->licenceNumber;
    }

    public function setLicenceNumber(?string $licenceNumber): static
    {
        $this->licenceNumber = $licenceNumber;

        return $this;
    }

    public function getAsaCode(): ?string
    {
        return $this->asaCode;
    }

    public function setAsaCode(?string $asaCode): static
    {
        $this->asaCode = $asaCode;

        return $this;
    }

    public function getType(): ?CommissaireType
    {
        return $this->type;
    }

    public function setType(?CommissaireType $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function isFlag(): ?bool
    {
        return $this->isFlag;
    }

    public function setIsFlag(bool $isFlag): static
    {
        $this->isFlag = $isFlag;

        return $this;
    }
}
