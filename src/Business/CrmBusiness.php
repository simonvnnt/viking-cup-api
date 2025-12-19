<?php

namespace App\Business;

use App\Helper\ConfigHelper;
use App\Repository\PersonRepository;
use App\Service\BrevoService;

readonly class CrmBusiness
{
    private array $brevoLists;

    public function __construct(
        private PersonRepository $personRepository,
        private BrevoService $brevoService,
        private ConfigHelper $configHelper
    )
    {
        $this->brevoLists = json_decode($this->configHelper->getValue('BREVO_LISTS_MAPPING'), true);
    }

    public function syncPilots(): void
    {
        $pilotPersons = $this->personRepository->findPilotsPersons();

        foreach ($pilotPersons as $pilotPerson) {
            $pilotListIds = [];
            foreach ($pilotPerson->getPilots() as $pilot) {
                foreach ($pilot->getPilotRoundCategories() as $pilotRoundCategory) {
                    $pilotListIds[] = $this->brevoLists['pilots']['season'][$pilot->getEvent()->getId()][$pilotRoundCategory->getCategory()->getId()] ?? null;
                    $pilotListIds[] = $this->brevoLists['pilots']['round'][$pilot->getEvent()->getId()][$pilotRoundCategory->getRound()->getId()] ?? null;
                }
            }

            $this->brevoService->createOrUpdateContact(
                $pilotPerson->getEmail(),
                [
                    'PRENOM' => $pilotPerson->getFirstName(),
                    'NOM' => $pilotPerson->getLastName(),
                    'EXT_ID' => $pilotPerson->getUniqueId(),
                ],
                array_values(array_unique($pilotListIds))
            );
        }
    }
}