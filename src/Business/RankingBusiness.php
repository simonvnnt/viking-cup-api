<?php

namespace App\Business;

use App\Entity\Category;
use App\Entity\Event;
use App\Entity\Round;

readonly class RankingBusiness
{
    public function __construct(
        private QualifyingBusiness $qualifyingBusiness,
        private BattleBusiness $battleBusiness,
        private RoundCategoryBusiness $roundCategoryBusiness
    )
    {}

    public function getRoundRanking(Round $round, Category $category): array
    {
        $qualifyingRanking = $this->qualifyingBusiness->getQualifyingRanking($round, $category);
        $battleRanking = $this->battleBusiness->getBattleRanking($round, $category);

        // sum of points in qualifying and battles for each pilot
        $roundRanking = $this->sumRoundRankingPoints($qualifyingRanking, $battleRanking);

        // subtract penalties
        $roundRanking = $this->subtractPenalties($roundRanking);

        // sort by total points
        usort($roundRanking, fn($a, $b) => $b['points'] <=> $a['points']);

        $roundRanking = array_map(fn($rank, $index) => [
            ...$rank,
            'position' => $index + 1
        ], $roundRanking, array_keys($roundRanking));

        if (!$this->roundCategoryBusiness->displayTop($category, $round)) {
            $roundRanking = array_filter($roundRanking, fn($rank) => $rank['position'] > 5);
        }

        return array_values($roundRanking);
    }

    public function getEventRanking(Event $event, Category $category): array
    {
        $eventRanking = [];
        foreach ($event->getRounds()->toArray() as $round) {
            $roundRanking = $this->getRoundRanking($round, $category);

            foreach ($roundRanking as $entry) {
                $pilot = $entry['pilot'];

                if (!isset($eventRanking[$pilot->getId()])) {
                    $eventRanking[$pilot->getId()] = [
                        'pilot' => $pilot,
                        'round' => $round,
                        'category' => $category,
                        'points' => 0,
                    ];
                }

                $eventRanking[$pilot->getId()]['points'] += $entry['points'];
            }
        }

        $eventRanking = array_values($eventRanking);

        // sort by total points
        usort($eventRanking, fn($a, $b) => $b['points'] <=> $a['points']);

        $eventRanking = array_map(fn($rank, $index) => [
            ...$rank,
            'position' => $index + 1
        ], $eventRanking, array_keys($eventRanking));

        if (!$this->roundCategoryBusiness->displayTop($category, null, $event)) {
            $eventRanking = array_filter($eventRanking, fn($rank) => $rank['position'] > 5);
        }

        return array_values($eventRanking);
    }

    private function sumRoundRankingPoints(array $qualifyingRanking, array $battleRanking): array
    {
        $rankingMerged = array_merge($qualifyingRanking, $battleRanking);

        $grouped = array_reduce($rankingMerged, function ($carry, $entry) {
            unset($entry['bestPassagePoints']);
            $pilotId = $entry['pilot']->getId();
            $points = $entry['points'];

            if (!isset($carry[$pilotId])) {
                $entry['points'] = 0;
                $carry[$pilotId] = $entry;
            }

            $carry[$pilotId]['points'] += $points;

            return $carry;
        }, []);

        return array_values($grouped);
    }

    private function subtractPenalties(array $roundRanking): array
    {
        foreach ($roundRanking as &$rank) {
            $pilotRoundCategory = $rank['pilot']->getPilotRoundCategories()->filter(fn($pilotRoundCategory) => $pilotRoundCategory->getCategory()->getId() === $rank['category']->getId() && $pilotRoundCategory->getRound()->getId() === $rank['round']->getId())->first();

            if ($pilotRoundCategory !== null) {
                $rank['points'] -= array_sum(array_map(fn($penalty) => $penalty->getPoints(), $pilotRoundCategory->getPenalties()->toArray()));
            }
        }

        return $roundRanking;
    }
}