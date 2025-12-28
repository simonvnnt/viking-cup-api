<?php

namespace App\Controller\Api;

use App\Business\TicketBusiness;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/ticket', name: 'api_ticket')]
class TicketApiController extends AbstractController
{
    #[Route('/sync/pilots', name: 'sync_pilots', methods: ['POST'])]
    public function syncPilots(TicketBusiness $ticketBusiness): Response
    {
        $ticketBusiness->syncPilots();

        return new Response();
    }

    #[Route('/sync/visitors', name: 'sync_visitors', methods: ['POST'])]
    public function syncVisitors(TicketBusiness $ticketBusiness): Response
    {
        $ticketBusiness->syncVisitors();

        return new Response();
    }
}