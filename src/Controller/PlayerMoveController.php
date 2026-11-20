<?php

namespace App\Controller;

use App\Dto\Game\PlayerMoveRequest;
use App\Entity\GameSession;
use App\Enum\GameStatus;
use App\Exception\OutOfBoundCubeException;
use App\Mapper\PlayerMoveResponseMapper;
use App\Service\DestroyCube;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

final class PlayerMoveController extends AbstractController
{
    #[Route('/api/games/{gameSession}/moves', name: 'app_player_move', methods: ['POST'], requirements: ['gameSession' => Requirement::UID_RFC4122])]
    public function index(
        GameSession $gameSession,
        #[MapRequestPayload] PlayerMoveRequest $playerMoveDTO,
        EntityManagerInterface $em,
        DestroyCube $destroyCube,
        PlayerMoveResponseMapper $mapper,
    ): JsonResponse {

        // A game that belongs to a user can only be played by this user.
        // A guest game (no owner) is played with its UUID.
        $owner = $gameSession->getUser();

        if (null !== $owner) {
            $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

            if ($owner !== $this->getUser()) {
                throw $this->createAccessDeniedException('Not the session owner.');
            }
        }

        //Game status control
        if (GameStatus::PLAYING !== $gameSession->getStatus()) {
            throw new ConflictHttpException('The game is already over.');
        }

        // hit management
        try {
            $moveOutCome = $destroyCube->compute(
                $gameSession->getPuzzle()->getObjectGrid(),
                $gameSession->getPlayerGrid(),
                $gameSession->getErrorsCount(),
                ['x' => $playerMoveDTO->x, 'y' => $playerMoveDTO->y, 'z' => $playerMoveDTO->z],
            );
        } catch (OutOfBoundCubeException $e) {
            throw new UnprocessableEntityHttpException($e->getMessage(), $e);
        }

        // Apply move outcome and persist
        $updatedGameSession = $gameSession->applyMoveOutCome($moveOutCome);
        $em->flush();

        // Map response in dtos (MoveResponse et VictoryMoveResponse).
        $result = $mapper->map($moveOutCome->moveResult,$updatedGameSession);

        //Return response WIP
        return $this->json([
            'result' => $result
        ]);
    }
}
