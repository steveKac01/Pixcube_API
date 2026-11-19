<?php

namespace App\Controller;

use App\Dto\Game\PlayerMoveRequest;
use App\Entity\GameSession;
use App\Enum\GameStatus;
use App\Repository\GameSessionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class PlayerMoveController extends AbstractController
{
    #[Route('/api/games/{gameSession}/moves', name: 'app_player_move', methods: ['POST'])]
    public function index(GameSession $gameSession, #[MapRequestPayload] PlayerMoveRequest $dto, GameSessionRepository $gameSessionRepository, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();

        //User control
        if ($user  !== $gameSession->getUser()) {

            return $user ?
                new JsonResponse(
                    ['result' => 'Not your puzzle'],
                    Response::HTTP_FORBIDDEN
                ) :  new JsonResponse(['result' => 'You need to be logged'], Response::HTTP_UNAUTHORIZED);
        }

        //Game status control
        if (GameStatus::PLAYING !== $gameSession->getStatus()) {
            return new JsonResponse(['result' => 'Puzzle is already ended'], Response::HTTP_CONFLICT);
        }

        // hit management WIP


        //Return response WIP
        return $this->json([
            $gameSession
        ]);
    }
}
