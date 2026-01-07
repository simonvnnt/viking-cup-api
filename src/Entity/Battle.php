<?php

namespace App\Entity;

use App\Repository\BattleRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: BattleRepository::class)]
class Battle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['battle'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'battles')]
    #[Groups(['battleLeader'])]
    private ?PilotRoundCategory $leader = null;

    #[ORM\ManyToOne(inversedBy: 'battles')]
    #[Groups(['battleChaser'])]
    private ?PilotRoundCategory $chaser = null;

    #[ORM\ManyToOne]
    #[Groups(['battleWinner'])]
    private ?PilotRoundCategory $winner = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['battle'])]
    private ?int $passage = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLeader(): ?PilotRoundCategory
    {
        return $this->leader;
    }

    public function setLeader(?PilotRoundCategory $leader): static
    {
        $this->leader = $leader;

        return $this;
    }

    public function getChaser(): ?PilotRoundCategory
    {
        return $this->chaser;
    }

    public function setChaser(?PilotRoundCategory $chaser): static
    {
        $this->chaser = $chaser;

        return $this;
    }

    public function getWinner(): ?PilotRoundCategory
    {
        return $this->winner;
    }

    public function setWinner(?PilotRoundCategory $winner): static
    {
        $this->winner = $winner;

        return $this;
    }

    public function getPassage(): ?int
    {
        return $this->passage;
    }

    public function setPassage(?int $passage): static
    {
        $this->passage = $passage;

        return $this;
    }
}
