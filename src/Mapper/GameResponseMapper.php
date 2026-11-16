<?php

namespace App\Mapper;

use App\Dto\Game\GameResponse;
use App\Entity\GameSession;
use App\Service\ClueGridCalculator;


/**
 * Turns a game session into the response sent to the front.
 */
final class GameResponseMapper
{
    public function __construct(
        private readonly ClueGridCalculator $clueCalculator,
    ) {}

    public function map(GameSession $game): GameResponse
    {
        $puzzle = $game->getPuzzle();

        return new GameResponse(
            gameId: $game->getId()->toRfc4122(),
            puzzleId: $puzzle->getId(),
            status: $game->getStatus(),
            errorsCount: $game->getErrorsCount(),
            clues: $this->clueCalculator->compute($puzzle->getObjectGrid()),
            playerGrid: $game->getPlayerGrid(),
        );
    }
}
