<?php

namespace App\Mapper;

use App\Dto\Game\PlayerMoveResponse;
use App\Dto\Game\PlayerVictoryMoveResponse;
use App\Entity\GameSession;
use App\Enum\GameStatus;
use App\Enum\MoveResult;


/**
 * Turns a game session into the response sent to the front.
 */
final class PlayerMoveResponseMapper
{
    public function __construct() {}

    public function map(MoveResult $moveResult, GameSession $gameSession): PlayerMoveResponse | PlayerMoveResponseMapper
    {
        if (GameStatus::VICTORY === $gameSession->getStatus()) {
            return new PlayerVictoryMoveResponse(
                $gameSession->getErrorsCount(),
                $gameSession->getPuzzle()->getName(),
                $gameSession->getPuzzle()->getObjectGrid()
            );
        }

        return new PlayerMoveResponse($moveResult, $gameSession->getErrorsCount());
    }
}
