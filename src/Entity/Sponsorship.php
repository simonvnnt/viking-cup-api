<?php

namespace App\Entity;

use App\Enum\SponsorCounterpartType;
use App\Enum\SponsorshipStatus;
use App\Repository\SponsorshipRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: SponsorshipRepository::class)]
class Sponsorship
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['sponsorship'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'sponsorships')]
    #[Groups(['sponsorshipSponsor'])]
    private ?Sponsor $sponsor = null;

    #[ORM\ManyToOne(inversedBy: 'sponsorships')]
    #[Groups(['sponsorshipEvent'])]
    private ?Event $event = null;

    #[ORM\ManyToOne(inversedBy: 'sponsorships')]
    #[Groups(['sponsorshipRound'])]
    private ?Round $round = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['sponsorship'])]
    private ?string $contractFilePath = null;

    #[ORM\Column(enumType: SponsorshipStatus::class)]
    #[Groups(['sponsorship'])]
    private ?SponsorshipStatus $status = null;

    /**
     * @var Collection<int, SponsorshipCounterpart>
     */
    #[ORM\OneToMany(targetEntity: SponsorshipCounterpart::class, mappedBy: 'sponsorship')]
    #[Groups(['sponsorshipCounterparts'])]
    private Collection $sponsorshipCounterparts;

    public function __construct()
    {
        $this->sponsorshipCounterparts = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSponsor(): ?Sponsor
    {
        return $this->sponsor;
    }

    public function setSponsor(?Sponsor $sponsor): static
    {
        $this->sponsor = $sponsor;

        return $this;
    }

    public function getEvent(): ?Event
    {
        return $this->event;
    }

    public function setEvent(?Event $event): static
    {
        $this->event = $event;

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

    public function getContractFilePath(): ?string
    {
        return $this->contractFilePath;
    }

    public function setContractFilePath(?string $contractFilePath): static
    {
        $this->contractFilePath = $contractFilePath;

        return $this;
    }

    public function getStatus(): ?SponsorshipStatus
    {
        return $this->status;
    }

    public function setStatus(SponsorshipStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return Collection<int, SponsorshipCounterpart>
     */
    public function getSponsorshipCounterparts(): Collection
    {
        return $this->sponsorshipCounterparts;
    }

    public function addSponsorCounterpart(SponsorshipCounterpart $sponsorCounterpart): static
    {
        if (!$this->sponsorshipCounterparts->contains($sponsorCounterpart)) {
            $this->sponsorshipCounterparts->add($sponsorCounterpart);
            $sponsorCounterpart->setSponsorship($this);
        }

        return $this;
    }

    public function removeSponsorCounterpart(SponsorshipCounterpart $sponsorCounterpart): static
    {
        if ($this->sponsorshipCounterparts->removeElement($sponsorCounterpart)) {
            // set the owning side to null (unless already changed)
            if ($sponsorCounterpart->getSponsorship() === $this) {
                $sponsorCounterpart->setSponsorship(null);
            }
        }

        return $this;
    }
}
