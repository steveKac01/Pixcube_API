<?php

namespace App\Controller;

use App\Entity\GameSession;
use App\Helper\PlayerGridInitiate;
use App\Mapper\GameResponseMapper;
use App\Repository\PuzzleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CreateGameController extends AbstractController
{
    #[Route('/api/games/{id}', name: 'app_create_game', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function index(
        PuzzleRepository $puzzleRepository,
        EntityManagerInterface $em,
        GameResponseMapper $mapper,
        ?int $id = null
    ): JsonResponse {

        // Id not found, create a tutorial game session
        if (null === $id) {
            $puzzle = $puzzleRepository->findTutorial();
        } else {
            // Other puzzle than the tutorial needs a user to be logged
            $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

            $puzzle = $puzzleRepository->find($id);
        }

        if (null === $puzzle || !$puzzle->isPublished()) {
            throw $this->createNotFoundException('Puzzle not found.');
        }

        // Create game session
        $game = (new GameSession())->setPuzzle($puzzle)
            ->setPlayerGrid((new PlayerGridInitiate())->compute($puzzle->getObjectGrid()))
            ->setUser($this->getUser());

        $em->persist($game);
        $em->flush();

        return new JsonResponse( $mapper->map($game), Response::HTTP_CREATED);
    }
}
