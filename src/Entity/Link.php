<?php

namespace App\Entity;

use App\Repository\LinkRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: LinkRepository::class)]
class Link
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['link', 'sponsor:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['link', 'sponsor:read'])]
    private ?string $url = null;

    #[ORM\ManyToOne(inversedBy: 'links')]
    #[Groups(['linkLinkType', 'sponsor:read'])]
    private ?LinkType $linkType = null;

    /**
     * @var Collection<int, Sponsor>
     */
    #[ORM\ManyToMany(targetEntity: Sponsor::class, mappedBy: 'links')]
    private Collection $sponsors;

    /**
     * @var Collection<int, Person>
     */
    #[ORM\ManyToMany(targetEntity: Person::class, mappedBy: 'links')]
    private Collection $people;

    public function __construct()
    {
        $this->sponsors = new ArrayCollection();
        $this->people = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function getLinkType(): ?LinkType
    {
        return $this->linkType;
    }

    public function setLinkType(?LinkType $linkType): static
    {
        $this->linkType = $linkType;

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
            $sponsor->addLink($this);
        }

        return $this;
    }

    public function removeSponsor(Sponsor $sponsor): static
    {
        if ($this->sponsors->removeElement($sponsor)) {
            $sponsor->removeLink($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Person>
     */
    public function getPeople(): Collection
    {
        return $this->people;
    }

    public function addPerson(Person $person): static
    {
        if (!$this->people->contains($person)) {
            $this->people->add($person);
            $person->addLink($this);
        }

        return $this;
    }

    public function removePerson(Person $person): static
    {
        if ($this->people->removeElement($person)) {
            $person->removeLink($this);
        }

        return $this;
    }
}
