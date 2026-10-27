<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class ProtectedController extends AbstractController
{
    #[Route('/api/protected', name: 'app_protected')]
    public function index(): JsonResponse
    {
        return new JsonResponse(['email' => $this->getUser()->getUserIdentifier()]);
    }
}
