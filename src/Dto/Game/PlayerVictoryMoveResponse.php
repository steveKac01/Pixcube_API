<?php

namespace App\Dto\Game;

use App\Enum\MoveResult;

/**
 * Response of the move after a victory (object name and his grid are reveal).
 * Sent only on victory, so the name and the solution can't leak in any other response.
 */
final readonly class PlayerVictoryMoveResponse extends PlayerMoveResponse
{
    /**
     * @param list<list<list<string|null>>> $objectGrid
     */
    public function __construct(
        int $errorsCount,
        public string $name,
        public array $objectGrid,
    ) {
        parent::__construct(MoveResult::VICTORY, $errorsCount);
    }
}
