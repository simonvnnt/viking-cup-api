<?php

namespace App\Business;

use App\Helper\ConfigHelper;
use App\Repository\PersonRepository;
use App\Service\BrevoService;
use Psr\Log\LoggerInterface;

readonly class CrmBusiness
{
    private array $brevoLists;

    public function __construct(
        private PersonRepository $personRepository,
        private BrevoService $brevoService,
        private ConfigHelper $configHelper,
        private LoggerInterface $logger
    )
    {
        $this->brevoLists = json_decode($this->configHelper->getValue('BREVO_LISTS_MAPPING'), true);
    }

    public function syncPilots(): void
    {
        $pilotPersons = $this->personRepository->findPilotsPersons();

        foreach ($pilotPersons as $pilotPerson) {
            try {
                $pilotListIds = [];
                foreach ($pilotPerson->getPilot()->getPilotRoundCategories() as $pilotRoundCategory) {
                    $pilotListIds[] = $this->brevoLists['pilots']['season'][$pilotRoundCategory->getRound()->getEvent()->getId()][$pilotRoundCategory->getCategory()->getId()] ?? null;
                    $pilotListIds[] = $this->brevoLists['pilots']['round'][$pilotRoundCategory->getRound()->getEvent()->getId()][$pilotRoundCategory->getRound()->getId()][$pilotRoundCategory->getCategory()->getId()] ?? null;
                }
                $attributes = [
                    'PRENOM' => $pilotPerson->getFirstName(),
                    'NOM' => $pilotPerson->getLastName(),
                    'EXT_ID' => $pilotPerson->getUniqueId(),
                ];
                if (!empty($pilotPerson->getPhone())) {
                    $attributes['SMS'] = $pilotPerson->getPhone();
                }
                $this->brevoService->createOrUpdateContact(
                    $pilotPerson->getEmail(),
                    $attributes,
                    array_values(array_unique($pilotListIds))
                );
            } catch (\Exception $e) {
                $this->logger->error('Error syncing pilot to Brevo: ' . $e->getMessage(), [
                    'person_id' => $pilotPerson->getId(),
                    'email' => $pilotPerson->getEmail(),
                ]);
            }
        }
    }
}