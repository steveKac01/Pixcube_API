<?php

namespace App\Controller;

use App\Entity\GameSession;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CreateGameController extends AbstractController
{
    #[Route('/api/games/{id}', name: 'app_create_game_with_id', methods: ['POST'])]
    public function index(CategoryRepository $categoryRepository, EntityManagerInterface $em, ?int $id = null): JsonResponse
    {
        // Id not found, create a tutorial game session
        if (!$id) {
            $categoryTuto = $categoryRepository->findOneBy(['slug' => 'tutorial']);

            if (null === $categoryTuto) {
                return new JsonResponse(['status' => 'Error, tutorial puzzle not founded'], Response::HTTP_NOT_FOUND);
            }

            $puzzle = $categoryTuto->getPuzzles()[0]; // Get tutorial puzzle

            if (!$puzzle) {
                return new JsonResponse(['status' => 'Error, tutorial puzzle not founded'], Response::HTTP_NOT_FOUND);
            }

            // Create game session
            $gameSession = (new GameSession())->setPuzzle($puzzle)
                ->setPlayerGrid([
                    [[0, 0], [0, 0]],
                    [[0, 0], [0, 0]],
                ])
                ->setUser($this->getUser());


            // Persist game session
            $em->persist($gameSession);
            $em->flush();

            // Returns game session


            return new JsonResponse(['tuto found']);
        }

        return new JsonResponse(['id' => $id], 200);
    }
}
