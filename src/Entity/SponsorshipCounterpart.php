<?php

namespace App\Entity;

use App\Enum\SponsorCounterpartType;
use App\Repository\SponsorshipCounterpartRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: SponsorshipCounterpartRepository::class)]
class SponsorshipCounterpart
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['sponsorshipCounterpart'])]
    private ?int $id = null;

    #[ORM\Column(enumType: SponsorCounterpartType::class)]
    #[Groups(['sponsorshipCounterpart'])]
    private ?SponsorCounterpartType $counterpartType = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['sponsorshipCounterpart'])]
    private ?float $amount = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['sponsorshipCounterpart'])]
    private ?string $otherCounterpart = null;

    #[ORM\ManyToOne(inversedBy: 'sponsorCounterparts')]
    private ?Sponsorship $sponsorship = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCounterpartType(): ?SponsorCounterpartType
    {
        return $this->counterpartType;
    }

    public function setCounterpartType(SponsorCounterpartType $counterpartType): static
    {
        $this->counterpartType = $counterpartType;

        return $this;
    }

    public function getAmount(): ?float
    {
        return $this->amount;
    }

    public function setAmount(?float $amount): static
    {
        $this->amount = $amount;

        return $this;
    }

    public function getOtherCounterpart(): ?string
    {
        return $this->otherCounterpart;
    }

    public function setOtherCounterpart(?string $otherCounterpart): static
    {
        $this->otherCounterpart = $otherCounterpart;

        return $this;
    }

    public function getSponsorship(): ?Sponsorship
    {
        return $this->sponsorship;
    }

    public function setSponsorship(?Sponsorship $sponsorship): static
    {
        $this->sponsorship = $sponsorship;

        return $this;
    }
}
