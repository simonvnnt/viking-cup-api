<?php

namespace App\Entity;

use App\Repository\MediaRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: MediaRepository::class)]
class Media
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups('media')]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups('media')]
    private ?string $insuranceFilePath = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups('media')]
    private ?string $bookFilePath = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups('media')]
    private ?string $pilotFollow = null;

    #[ORM\Column]
    #[Groups('media')]
    private ?bool $selected = false;

    #[ORM\Column]
    #[Groups('media')]
    private ?bool $selectedMailSent = false;

    #[ORM\Column]
    #[Groups('media')]
    private ?bool $eLearningMailSent = false;

    #[ORM\Column]
    #[Groups('media')]
    private ?bool $briefingSeen = false;

    #[ORM\Column]
    #[Groups('media')]
    private ?bool $generatePass = false;

    #[ORM\ManyToOne(inversedBy: 'medias')]
    #[Groups('mediaRound')]
    private ?Round $round = null;

    #[ORM\ManyToOne(inversedBy: 'medias')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups('mediaPerson')]
    private ?Person $person = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getInsuranceFilePath(): ?string
    {
        return $this->insuranceFilePath;
    }

    public function setInsuranceFilePath(?string $insuranceFilePath): static
    {
        $this->insuranceFilePath = $insuranceFilePath;

        return $this;
    }

    public function getBookFilePath(): ?string
    {
        return $this->bookFilePath;
    }

    public function setBookFilePath(?string $bookFilePath): static
    {
        $this->bookFilePath = $bookFilePath;

        return $this;
    }

    public function getPilotFollow(): ?string
    {
        return $this->pilotFollow;
    }

    public function setPilotFollow(?string $pilotFollow): static
    {
        $this->pilotFollow = $pilotFollow;

        return $this;
    }

    public function isSelected(): ?bool
    {
        return $this->selected;
    }

    public function setSelected(bool $selected): static
    {
        $this->selected = $selected;

        return $this;
    }

    public function isSelectedMailSent(): ?bool
    {
        return $this->selectedMailSent;
    }

    public function setSelectedMailSent(bool $selectedMailSent): static
    {
        $this->selectedMailSent = $selectedMailSent;

        return $this;
    }

    public function isELearningMailSent(): ?bool
    {
        return $this->eLearningMailSent;
    }

    public function setELearningMailSent(bool $eLearningMailSent): static
    {
        $this->eLearningMailSent = $eLearningMailSent;

        return $this;
    }

    public function isBriefingSeen(): ?bool
    {
        return $this->briefingSeen;
    }

    public function setBriefingSeen(bool $briefingSeen): static
    {
        $this->briefingSeen = $briefingSeen;

        return $this;
    }

    public function isGeneratePass(): ?bool
    {
        return $this->generatePass;
    }

    public function setGeneratePass(bool $generatePass): static
    {
        $this->generatePass = $generatePass;

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

    public function getPerson(): ?Person
    {
        return $this->person;
    }

    public function setPerson(?Person $person): static
    {
        $this->person = $person;

        return $this;
    }
}
