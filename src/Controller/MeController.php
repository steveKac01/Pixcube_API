<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class MeController extends AbstractController
{
    #[Route('/api/me', name: 'app_me')]
    public function index(): JsonResponse
    {
        return new JsonResponse([
            'email' => $this->getUser()->getUserIdentifier(),
            'roles' => $this->getUser()->getRoles()
        ]);
    }
}
