<?php

namespace App\Business;

use App\Entity\Battle;
use App\Entity\Category;
use App\Entity\Pilot;
use App\Entity\PilotRoundCategory;
use App\Entity\Round;
use App\Helper\RankingHelper;
use App\Repository\PilotRoundCategoryRepository;
use App\Repository\RankingPointsRepository;
use App\Repository\BattleRepository;
use App\Repository\BattleVersusRepository;
use App\Repository\RoundCategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

readonly class BattleBusiness
{
    public function __construct(
        private BattleVersusRepository  $battleVersusRepository,
        private BattleRepository        $battleRepository,
        private RankingPointsRepository $rankingPointsRepository,
        private PilotRoundCategoryRepository $pilotRoundCategoryRepository,
        private RoundCategoryRepository $roundCategoryRepository,
        private QualifyingBusiness      $qualifyingBusiness,
        private RoundCategoryBusiness   $roundCategoryBusiness,
        private RankingHelper           $rankingHelper,
        private EntityManagerInterface  $em
    )
    {}

    public function getBattleVersus(Round $round, Category $category): array
    {
        return $this->battleRepository->getBattleVersus($round, $category);
    }

    public function resetBattle(Round $round, Category $category, ?int $passage = null): void
    {
        $battles = $this->battleRepository->getBattleVersus($round, $category, $passage);

        foreach ($battles as $battle) {
            $this->em->remove($battle);
        }

        $this->em->flush();
    }

    public function initBattleVersus(Round $round, Category $category): void
    {
        $battleVersus = $this->battleVersusRepository->findAll();
        $qualifyingRanking = $this->qualifyingBusiness->getQualifyingRanking($round, $category);

        $qualifyingRanking = $this->getBattleQualifier($category, $qualifyingRanking);

        if (empty($qualifyingRanking)) {
            throw new Exception('Aucun pilote qualifié pour cette catégorie.');
        }

        foreach ($battleVersus as $versus) {
            /** @var Pilot $pilot1 */
            $pilot1 = $qualifyingRanking[$versus->getPilotQualifPosition1() -1]['pilot'] ?? null;
            $pilotRoundCategory1 = $pilot1?->getPilotRoundCategories()->filter(fn(PilotRoundCategory $pilotRoundCategory) => $pilotRoundCategory->getRound()->getId() === $round->getId() && $pilotRoundCategory->getCategory()->getId() === $category->getId() && $pilotRoundCategory->isCompeting() === true && $pilotRoundCategory->isEngaged() === true)->first();
            $pilotRoundCategory1 = $pilotRoundCategory1 !== false ? $pilotRoundCategory1 : null;

            $pilot2 = $qualifyingRanking[$versus->getPilotQualifPosition2() -1]['pilot'] ?? null;
            $pilotRoundCategory2 = $pilot2?->getPilotRoundCategories()->filter(fn(PilotRoundCategory $pilotRoundCategory) => $pilotRoundCategory->getRound()->getId() === $round->getId() && $pilotRoundCategory->getCategory()->getId() === $category->getId() && $pilotRoundCategory->isCompeting() === true && $pilotRoundCategory->isEngaged() === true)->first();
            $pilotRoundCategory2 = $pilotRoundCategory2 !== false ? $pilotRoundCategory2 : null;

            // cas double monte
            if ($pilotRoundCategory1 !== null && $pilotRoundCategory2 !== null) {
                if ($pilotRoundCategory1->getSecondPilot()?->getId() === $pilotRoundCategory2->getPilot()->getId() ||
                    $pilotRoundCategory2->getSecondPilot()?->getId() === $pilotRoundCategory1->getPilot()->getId()) {

                    $battle = new Battle();
                        $battle->setLeader($pilotRoundCategory1)
                        ->setChaser($pilotRoundCategory2)
                        ->setWinner($pilotRoundCategory1->isMainPilot() ? $pilotRoundCategory1 : $pilotRoundCategory2)
                        ->setPassage(1);
                    $this->em->persist($battle);

                    continue;
                }
            }

            // cas classique
            $battle = new Battle();
            $battle->setLeader($pilotRoundCategory1)
                ->setChaser($pilotRoundCategory2)
                ->setPassage(1);

            $this->em->persist($battle);
        }

        $this->em->flush();
    }

    public function getBattleQualifier(Category $category, array $qualifyingRanking): array
    {
        // if category "Loisir" (id 1) => 16 first pilots
        if ($category->getId() === 1) {
            $battleQualifiers = array_slice($qualifyingRanking, 0, 16);
            $outBattleQualifiers = array_slice($qualifyingRanking, 16);
        } else {
            $battleQualifiers = array_slice($qualifyingRanking, 0, 32);
            $outBattleQualifiers = array_slice($qualifyingRanking, 32);
        }

        // don't keep qualifiers with point 0 as the best passage
        $battleQualifiers = array_filter($battleQualifiers, fn($item) => isset($item['bestPassagePoints']) && $item['bestPassagePoints'] > 0);

        $lastQualif = end($battleQualifiers);
        $lastQualifPoints = $lastQualif['bestPassagePoints'] ?? 0;

        // if $lastQualif is in $outBattleQualifiers
        $outQualifWithSamePoints = array_filter($outBattleQualifiers, fn($item) => $item['bestPassagePoints'] === $lastQualifPoints);
        if (count($outQualifWithSamePoints) > 0) {
            // remove all elements with $lastQualifPoints in $battleQualifiers
            $battleQualifiers = array_filter($battleQualifiers, fn($item) => $item['bestPassagePoints'] !== $lastQualifPoints);
        }

        return $battleQualifiers;
    }

    public function generateNextRound(Round $round, Category $category, int $passage): void
    {
        $nextPassage = $passage + 1;
        $nextBattles = $this->battleRepository->getBattleVersus($round, $category, $nextPassage);

        if (count($nextBattles) > 0) {
            throw new Exception("Le tour suivant a déjà été généré !");
        }

        $winners = $this->battleRepository->getBattleVersus($round, $category, $passage);

        if ($nextPassage === 5) {
            $this->generateThirdPlacePlayoff($round,$category, $winners, $nextPassage + 1);
        }

        for ($i = 0; $i < count($winners); $i += 2) {
            $winner1 = isset($winners[$i]) ? $winners[$i]->getWinner() : null;
            if ($winner1?->isCompeting() === false) {
                $winner1 = null;
            }
            $winner2 = isset($winners[$i + 1]) ? $winners[$i + 1]->getWinner() : null;
            if ($winner2?->isCompeting() === false) {
                $winner2 = null;
            }

            $battle = new Battle();
            $battle->setLeader($winner1)
                ->setChaser($winner2)
                ->setPassage($nextPassage);

            $this->em->persist($battle);
        }

        $this->em->flush();
    }

    public function setBattleWinner(Round $round, Category $category, Battle $battle, ?PilotRoundCategory $winner): void
    {
        $battle->setWinner($winner);
        $this->em->persist($battle);
        $this->em->flush();

        $this->recursiveRegenerateNextRound($round, $category, $battle->getPassage());
    }

    public function recursiveRegenerateNextRound(Round $round, Category $category, int $passage): void
    {
        $nextPassage = $passage + 1;
        $nextBattles = $this->battleRepository->getBattleVersus($round, $category, $nextPassage);

        if (count($nextBattles) > 0) {
            foreach ($nextBattles as $nextBattle) {
                $this->em->remove($nextBattle);
            }
            $this->em->flush();

            $this->generateNextRound($round, $category, $passage);

            $this->recursiveRegenerateNextRound($round, $category, $nextPassage);
        }
    }

    public function getBattleRanking(Round $round, Category $category): array
    {
        if ($round->getId() === 1) {
            $pilotRoundCategories = $this->pilotRoundCategoryRepository->findBy(['round' => $round, 'category' => $category]);
            $battlesRanking = $this->overrideBattleRound1($pilotRoundCategories, $category->getId());
        } else {
            $battlesRanking = $this->battleRepository->getBattleRanking($round, $category);

            // sort by wins DESC, last_defeat_passage 0 first else last_defeat_passage DESC
            usort($battlesRanking, function ($a, $b) {
                if ($a['wins'] === $b['wins']) {
                    if ($a['last_defeat_passage'] === 0 && $b['last_defeat_passage'] !== 0) {
                        return -1;
                    } elseif ($a['last_defeat_passage'] !== 0 && $b['last_defeat_passage'] === 0) {
                        return 1;
                    } else {
                        return $b['last_defeat_passage'] <=> $a['last_defeat_passage'];
                    }
                }

                return $b['wins'] <=> $a['wins'];
            });

            if (!$this->isBattleFinished($round, $category)) {
                $battlesRanking = array_filter($battlesRanking, fn($battle) => $battle['last_defeat_passage'] !== 0);
            }

            // On mappe pour enrichir avec les infos utiles à l’affichage
            $battlesRanking = array_map(fn($battle, $index) => [
                'pilot' => isset($battle[0]) ? $battle[0]->getPilot() : null,
                'pilotEvent' => isset($battle[0]) ? $battle[0]->getPilot()->getPilotEvents()->filter(fn($pe) => $pe->getEvent()->getId() === $round->getEvent()->getId())->first() : null,
                'round' => $round,
                'category' => $category,
                'position' => $index + 1
            ], $battlesRanking, array_keys($battlesRanking));

            if (!$this->roundCategoryBusiness->displayTop($category, $round)) {
                $battlesRanking = array_filter($battlesRanking, fn($battle) => $battle['position'] > 5);
            }

            $battleRankingPoints = $this->rankingPointsRepository->findBy(['entity' => 'battle']);
            foreach ($battlesRanking as &$battleRanking) {
                $battleRanking['points'] = $this->rankingHelper->getPointsByPosition($battleRanking['position'], $battleRankingPoints);
            }
        }

        return $battlesRanking;
    }

    private function generateThirdPlacePlayoff(Round $round, Category $category, array $winners, int $passage): void
    {
        $losers = array_map(fn(Battle $battle) => $battle->getWinner() === $battle->getLeader() ? $battle->getChaser() : $battle->getLeader(), $winners);

        if (count($losers) !== 2) {
            return;
        }

        $qualifyingRanking = $this->qualifyingBusiness->getQualifyingRanking($round, $category);

        $rankingMap = [];
        foreach ($qualifyingRanking as $rank) {
            if (isset($rank['pilot'])) {
                $rankingMap[$rank['pilot']->getId()] = $rank['position'];
            }
        }

        usort($losers, function (PilotRoundCategory $loser1, PilotRoundCategory $loser2) use ($rankingMap) {
            $position1 = $rankingMap[$loser1->getPilot()->getId()] ?? PHP_INT_MAX;
            $position2 = $rankingMap[$loser2->getPilot()->getId()] ?? PHP_INT_MAX;

            return $position1 <=> $position2;
        });

        $battle = new Battle();
        $battle->setLeader($losers[0])
            ->setChaser($losers[1])
            ->setPassage($passage);

        $this->em->persist($battle);
    }

    private function isBattleFinished(Round $round, Category $category): bool
    {
        $maxPassage = $this->battleRepository->getMaxPassage($round, $category);
        if ($maxPassage < 6) {
            return false;
        }

        return true;
    }

    public function overrideBattleRound1(array $pilotRoundCategories, int $categoryId): array
    {
        if ($categoryId === 1) {
            $overrideRanking = [
                ['pilotId' => 13, 'position' => 0, 'points' => 200], // DE CARVALHO
                ['pilotId' => 15, 'position' => 1, 'points' => 150], // VICENTE
                ['pilotId' => 63, 'position' => 2, 'points' => 100], // KARROUACHE
                ['pilotId' => 50, 'position' => 3, 'points' => 50], // BREANT
                ['pilotId' => 57, 'position' => 4, 'points' => 30], // DA COSTA
                ['pilotId' => 24, 'position' => 5, 'points' => 30], // RAKOTONDRATRIMO
                ['pilotId' => 10, 'position' => 6, 'points' => 30], // FELIX
                ['pilotId' => 54, 'position' => 5, 'points' => 30], // FAVIER
                ['pilotId' => 60, 'position' => 6, 'points' => 20], // LEGROS
                ['pilotId' => 37, 'position' => 7, 'points' => 20], // DANGEON
                ['pilotId' => 33, 'position' => 8, 'points' => 20], // DELAUNAY
                ['pilotId' => 53, 'position' => 9, 'points' => 20], // BONNARD
                ['pilotId' => 62, 'position' => 10, 'points' => 20], // FAUVEAU
                ['pilotId' => 61, 'position' => 11, 'points' => 20], // LAMBERT
                ['pilotId' => 9, 'position' => 12, 'points' => 20] // GLOAGUEN
            ];
        } elseif ($categoryId === 2) {
            $overrideRanking = [
                ['pilotId' => 48, 'position' => 0, 'points' => 200], // VAN WEYMEERSCH
                ['pilotId' => 55, 'position' => 1, 'points' => 150], // DUCRET
                ['pilotId' => 59, 'position' => 2, 'points' => 100], // TROSSET
                ['pilotId' => 58, 'position' => 3, 'points' => 50], // LA RUSSA
                ['pilotId' => 36, 'position' => 4, 'points' => 30], // THOUIN
                ['pilotId' => 22, 'position' => 5, 'points' => 30], // SERABIAN
                ['pilotId' => 30, 'position' => 6, 'points' => 30], // MOREIRA
                ['pilotId' => 23, 'position' => 7, 'points' => 30], // GUY
                ['pilotId' => 3, 'position' => 8, 'points' => 20], // GENIEYS
                ['pilotId' => 41, 'position' => 9, 'points' => 20], // MARIEN
                ['pilotId' => 16, 'position' => 10, 'points' => 20], // MAON
                ['pilotId' => 11, 'position' => 11, 'points' => 20], // PREVOST
                ['pilotId' => 25, 'position' => 12, 'points' => 20], // PIOGE
                ['pilotId' => 1, 'position' => 13, 'points' => 20], // SANTOS
            ];
        }

        $ranking = [];
        if (isset($overrideRanking)) {
            /** @var PilotRoundCategory $pilotRoundCategory */
            foreach ($pilotRoundCategories as $pilotRoundCategory) {
                $pilotOverrideIndex = array_search($pilotRoundCategory->getPilot()->getId(), array_column($overrideRanking, 'pilotId'));
                if ($pilotOverrideIndex === false) {
                    continue;
                }
                $pilotOverride = $overrideRanking[$pilotOverrideIndex];

                $ranking[] = [
                    'pilot' => $pilotRoundCategory->getPilot(),
                    'pilotEvent' => $pilotRoundCategory->getPilot()->getPilotEvents()->filter(fn($pe) => $pe->getEvent()->getId() === $pilotRoundCategory->getRound()->getEvent()->getId())->first(),
                    'round' => $pilotRoundCategory->getRound(),
                    'category' => $pilotRoundCategory->getCategory(),
                    'points' => $pilotOverride['points'],
                    'position' => $pilotOverride['position']
                ];
            }

            // Tri par position croissante
            usort($ranking, fn($a, $b) => $a['position'] <=> $b['position']);
        }

        return $ranking;
    }
}
