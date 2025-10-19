<?php

namespace App\Helper;

use App\Entity\Category;
use App\Entity\Event;
use App\Entity\Pilot;
use App\Entity\PilotNumberCounter;
use App\Repository\PilotEventRepository;
use Doctrine\ORM\EntityManagerInterface;

readonly class PilotHelper
{
    public function __construct(
        private PilotEventRepository $pilotEventRepository,
        private EntityManagerInterface $em
    ) {
    }

    public function getPilotNumber(Event $event, Category $category, ?int $pilotNumber = null): ?int
    {
        $pilotNumberCounter = $event->getPilotNumberCounters()->filter(fn (PilotNumberCounter $counter) => $counter->getCategory()?->getId() === $category->getId())->first();
        if ($pilotNumberCounter === false) {
            return null;
        }

        if (!$this->isValidPilotNumber($pilotNumber)) {
            $pilotNumber = $pilotNumberCounter->getPilotNumberCounter() + 1;
        }

        $pilotNumberCounter->setPilotNumberCounter($pilotNumber);
        $this->em->persist($pilotNumberCounter);

        return $pilotNumber;
    }

    private function isValidPilotNumber(?int $pilotNumber): bool
    {
        $pilotEvent = $this->pilotEventRepository->findBy(['pilotNumber' => $pilotNumber]);

        return $pilotNumber !== null && $pilotNumber > 0 && count($pilotEvent) === 0;
    }
}