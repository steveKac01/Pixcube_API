<?php

namespace App\Dto\Game;

use App\Enum\MoveResult;

/**
 * Response after a player's move.
 */
readonly class PlayerMoveResponse
{
    public function __construct(
        public MoveResult $result,
        public int $errorsCount,
    ) {}
}
