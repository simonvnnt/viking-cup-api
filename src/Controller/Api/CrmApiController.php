<?php

namespace App\Controller\Api;

use App\Business\BattleBusiness;
use App\Business\CrmBusiness;
use App\Entity\Battle;
use App\Entity\PilotRoundCategory;
use App\Repository\CategoryRepository;
use App\Repository\RoundRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/crm', name: 'api_crm')]
class CrmApiController extends AbstractController
{
    #[Route('/pilots', name: 'crm_pilots', methods: ['POST'])]
    public function syncPilots(
        CrmBusiness $crmBusiness
    ): Response
    {
        $crmBusiness->syncPilots();

        return new Response();
    }
}