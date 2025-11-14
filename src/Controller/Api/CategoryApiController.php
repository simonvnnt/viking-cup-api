<?php

namespace App\Controller\Api;

use App\Business\CategoryBusiness;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/categories', name: 'api_categories')]
class CategoryApiController extends AbstractController
{
    #[Route('', name: 'list', methods: ['GET'])]
    public function getCategories(
        CategoryBusiness $categoryBusiness
    ): Response
    {
        $categories = $categoryBusiness->getCategories();

        return $this->json($categories, 200, [], ['groups' => ['category', 'categoryEvents', 'event']]);
    }
}