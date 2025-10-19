<?php

namespace App\Business;

use App\Entity\Event;
use App\Entity\Round;
use App\Repository\EventRepository;
use DateTime;
use DateTimeZone;

readonly class EventBusiness
{
    public function __construct(
        private EventRepository $eventRepository
    )
    {}

    public function getEvents(): array
    {
        return $this->eventRepository->findAll();
    }

    public function getCurrentSeason(): ?Event
    {
        $now = new DateTime('now', new DateTimeZone('Europe/Paris'));

        $currentSeason = $this->eventRepository->findOneBy(['year' => $now->format('Y')]);

        // if there is no round after the current date
        // get the next event
        if (!$currentSeason->getRounds()->exists(fn($key, Round $round) => $round->getToDate() >= $now)) {
            $currentSeason = $this->eventRepository->findOneBy(['year' => (int)$now->format('Y') + 1]);
        }

        return $currentSeason;
    }
}