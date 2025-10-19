<?php

namespace App\Entity;

use App\Repository\PersonRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: PersonRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Person
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups('person')]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups('person')]
    private ?string $firstName = null;

    #[ORM\Column(length: 255)]
    #[Groups('person')]
    private ?string $lastName = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups('person')]
    private ?string $phone = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups('person')]
    private ?string $email = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['person'])]
    private ?string $address = null;

    #[ORM\Column(length: 10, nullable: true)]
    #[Groups(['person'])]
    private ?string $zipCode = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['person'])]
    private ?string $city = null;

    #[ORM\Column(length: 5, nullable: true)]
    #[Groups(['person'])]
    private ?string $country = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['person'])]
    private ?string $nationality = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups('person')]
    private ?string $role = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups('person')]
    private ?string $comment = null;

    #[ORM\Column]
    #[Groups('person')]
    private ?int $warnings = 0;

    /**
     * @var Collection<int, Link>
     */
    #[ORM\ManyToMany(targetEntity: Link::class, inversedBy: 'people')]
    #[Groups('personLinks')]
    private Collection $links;

    #[ORM\Column(length: 255)]
    private ?string $uniqueId = null;

    /**
     * @var Collection<int, Round>
     */
    #[ORM\ManyToMany(targetEntity: Round::class, inversedBy: 'people')]
    #[Groups('personRounds')]
    private Collection $rounds;

    /**
     * @var Collection<int, RoundDetail>
     */
    #[ORM\ManyToMany(targetEntity: RoundDetail::class, inversedBy: 'people')]
    #[Groups('personRoundDetails')]
    private Collection $roundDetails;

    /**
     * @var Collection<int, Media>
     */
    #[ORM\OneToMany(targetEntity: Media::class, mappedBy: 'person')]
    #[Groups(['personMedias'])]
    private Collection $medias;

    #[ORM\OneToOne(mappedBy: 'person', cascade: ['persist', 'remove'])]
    #[Groups('personMember')]
    private ?Member $member = null;

    /**
     * @var Collection<int, Commissaire>
     */
    #[ORM\OneToMany(targetEntity: Commissaire::class, mappedBy: 'person')]
    #[Groups('personCommissaires')]
    private Collection $commissaires;

    /**
     * @var Collection<int, Volunteer>
     */
    #[ORM\OneToMany(targetEntity: Volunteer::class, mappedBy: 'person')]
    #[Groups('personVolunteers')]
    private Collection $volunteers;

    /**
     * @var Collection<int, Rescuer>
     */
    #[ORM\OneToMany(targetEntity: Rescuer::class, mappedBy: 'person')]
    #[Groups('personRescuers')]
    private Collection $rescuers;

    #[ORM\OneToOne(mappedBy: 'person', cascade: ['persist', 'remove'])]
    #[Groups('personPilot')]
    private ?Pilot $pilot = null;

    /**
     * @var Collection<int, Visitor>
     */
    #[ORM\OneToMany(targetEntity: Visitor::class, mappedBy: 'person')]
    private Collection $visitors;

    /**
     * @var Collection<int, Sponsor>
     */
    #[ORM\OneToMany(targetEntity: Sponsor::class, mappedBy: 'contact')]
    private Collection $sponsors;

    public function __construct()
    {
        $this->links = new ArrayCollection();
        $this->rounds = new ArrayCollection();
        $this->roundDetails = new ArrayCollection();
        $this->medias = new ArrayCollection();
        $this->commissaires = new ArrayCollection();
        $this->volunteers = new ArrayCollection();
        $this->rescuers = new ArrayCollection();
        $this->visitors = new ArrayCollection();
        $this->sponsors = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function updatedTimestamps(): void
    {
        if ($this->uniqueId === null) {
            $this->uniqueId = uniqid('', true);
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): static
    {
        $this->address = $address;

        return $this;
    }

    public function getZipCode(): ?string
    {
        return $this->zipCode;
    }

    public function setZipCode(?string $zipCode): static
    {
        $this->zipCode = $zipCode;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): static
    {
        $this->country = $country;

        return $this;
    }

    public function getNationality(): ?string
    {
        return $this->nationality;
    }

    public function setNationality(?string $nationality): static
    {
        $this->nationality = $nationality;

        return $this;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(?string $role): static
    {
        $this->role = $role;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): static
    {
        $this->comment = $comment;

        return $this;
    }

    public function getWarnings(): ?int
    {
        return $this->warnings;
    }

    public function setWarnings(int $warnings): static
    {
        $this->warnings = $warnings;

        return $this;
    }

    /**
     * @return Collection<int, Link>
     */
    public function getLinks(): Collection
    {
        return $this->links;
    }

    public function addLink(Link $link): static
    {
        if (!$this->links->contains($link)) {
            $this->links->add($link);
        }

        return $this;
    }

    public function removeLink(Link $link): static
    {
        $this->links->removeElement($link);

        return $this;
    }

    public function getUniqueId(): ?string
    {
        return $this->uniqueId;
    }

    public function setUniqueId(string $uniqueId): static
    {
        $this->uniqueId = $uniqueId;

        return $this;
    }

    /**
     * @return Collection<int, Round>
     */
    public function getRounds(): Collection
    {
        return $this->rounds;
    }

    public function addRound(Round $round): static
    {
        if (!$this->rounds->contains($round)) {
            $this->rounds->add($round);
        }

        return $this;
    }

    public function removeRound(Round $round): static
    {
        $this->rounds->removeElement($round);

        return $this;
    }

    /**
     * @return Collection<int, RoundDetail>
     */
    public function getRoundDetails(): Collection
    {
        return $this->roundDetails;
    }

    public function addRoundDetail(RoundDetail $roundDetail): static
    {
        if (!$this->roundDetails->contains($roundDetail)) {
            $this->roundDetails->add($roundDetail);
        }

        return $this;
    }

    public function removeRoundDetail(RoundDetail $roundDetail): static
    {
        $this->roundDetails->removeElement($roundDetail);

        return $this;
    }

    /**
     * @return Collection<int, Media>
     */
    public function getMedias(): Collection
    {
        return $this->medias;
    }

    public function addMedia(Media $media): static
    {
        if (!$this->medias->contains($media)) {
            $this->medias->add($media);
            $media->setPerson($this);
        }

        return $this;
    }

    public function removeMedia(Media $media): static
    {
        if ($this->medias->removeElement($media)) {
            // set the owning side to null (unless already changed)
            if ($media->getPerson() === $this) {
                $media->setPerson(null);
            }
        }

        return $this;
    }

    public function getMember(): ?Member
    {
        return $this->member;
    }

    public function setMember(?Member $member): static
    {
        // unset the owning side of the relation if necessary
        if ($member === null && $this->member !== null) {
            $this->member->setPerson(null);
        }

        // set the owning side of the relation if necessary
        if ($member !== null && $member->getPerson() !== $this) {
            $member->setPerson($this);
        }

        $this->member = $member;

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
            $commissaire->setPerson($this);
        }

        return $this;
    }

    public function removeCommissaire(Commissaire $commissaire): static
    {
        if ($this->commissaires->removeElement($commissaire)) {
            // set the owning side to null (unless already changed)
            if ($commissaire->getPerson() === $this) {
                $commissaire->setPerson(null);
            }
        }

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
            $volunteer->setPerson($this);
        }

        return $this;
    }

    public function removeVolunteer(Volunteer $volunteer): static
    {
        if ($this->volunteers->removeElement($volunteer)) {
            // set the owning side to null (unless already changed)
            if ($volunteer->getPerson() === $this) {
                $volunteer->setPerson(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Rescuer>
     */
    public function getRescuers(): Collection
    {
        return $this->rescuers;
    }

    public function addRescuer(Rescuer $rescuer): static
    {
        if (!$this->rescuers->contains($rescuer)) {
            $this->rescuers->add($rescuer);
            $rescuer->setPerson($this);
        }

        return $this;
    }

    public function removeRescuer(Rescuer $rescuer): static
    {
        if ($this->rescuers->removeElement($rescuer)) {
            // set the owning side to null (unless already changed)
            if ($rescuer->getPerson() === $this) {
                $rescuer->setPerson(null);
            }
        }

        return $this;
    }

    public function getPilot(): ?Pilot
    {
        return $this->pilot;
    }

    public function setPilot(?Pilot $pilot): static
    {
        // unset the owning side of the relation if necessary
        if ($pilot === null && $this->pilot !== null) {
            $this->pilot->setPerson(null);
        }

        // set the owning side of the relation if necessary
        if ($pilot !== null && $pilot->getPerson() !== $this) {
            $pilot->setPerson($this);
        }

        $this->pilot = $pilot;

        return $this;
    }

    /**
     * @return Collection<int, Visitor>
     */
    public function getVisitors(): Collection
    {
        return $this->visitors;
    }

    public function addVisitor(Visitor $visitor): static
    {
        if (!$this->visitors->contains($visitor)) {
            $this->visitors->add($visitor);
            $visitor->setPerson($this);
        }

        return $this;
    }

    public function removeVisitor(Visitor $visitor): static
    {
        if ($this->visitors->removeElement($visitor)) {
            // set the owning side to null (unless already changed)
            if ($visitor->getPerson() === $this) {
                $visitor->setPerson(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Sponsor>
     */
    public function getSponsors(): Collection
    {
        return $this->sponsors;
    }

    public function addSponsor(Sponsor $sponsor): static
    {
        if (!$this->sponsors->contains($sponsor)) {
            $this->sponsors->add($sponsor);
            $sponsor->setContact($this);
        }

        return $this;
    }

    public function removeSponsor(Sponsor $sponsor): static
    {
        if ($this->sponsors->removeElement($sponsor)) {
            // set the owning side to null (unless already changed)
            if ($sponsor->getContact() === $this) {
                $sponsor->setContact(null);
            }
        }

        return $this;
    }
}
