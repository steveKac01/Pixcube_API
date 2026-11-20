<?php

namespace App\Mapper;

use App\Dto\Game\PlayerMoveResponse;
use App\Dto\Game\PlayerVictoryMoveResponse;
use App\Entity\GameSession;
use App\Enum\GameStatus;
use App\Enum\MoveResult;


/**
 * Turns the result of a move into the response sent to the front.
 * Only a game won reveals the object.
 */
final class PlayerMoveResponseMapper
{
    public function map(MoveResult $moveResult, GameSession $gameSession): PlayerMoveResponse
    {
        if (GameStatus::VICTORY !== $gameSession->getStatus()) {
            return new PlayerMoveResponse($moveResult, $gameSession->getErrorsCount());
        }

        // Warning: the only place where the name and the solution grid are reveal
        $puzzle = $gameSession->getPuzzle();
        return new PlayerVictoryMoveResponse(
            $gameSession->getErrorsCount(),
            $puzzle->getName(),
            $puzzle->getObjectGrid(),
        );
    }
}
