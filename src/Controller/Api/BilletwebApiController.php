<?php

namespace App\Controller\Api;

use App\Business\BilletwebBusiness;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/billetweb', name: 'api_billetweb')]
class BilletwebApiController extends AbstractController
{
    #[Route('/sync/pilots', name: 'sync_pilots', methods: ['POST'])]
    public function syncPilots(BilletwebBusiness $billetwebBusiness): Response
    {
        $billetwebBusiness->syncPilots();

        return new Response();
    }

    #[Route('/sync/visitors', name: 'sync_visitors', methods: ['POST'])]
    public function syncVisitors(BilletwebBusiness $billetwebBusiness): Response
    {
        $billetwebBusiness->syncVisitors();

        return new Response();
    }
}