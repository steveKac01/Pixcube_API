<?php

namespace App\Game;

use App\Enum\MoveResult;

/**
 * The result after a player's move.
 * @param list<list<list<int>>> $playerGrid the player's grid after the move
 */
class MoveOutcome
{
    public function __construct(public MoveResult $moveResult, public array $playerGrid, public int $errorsCount) {}
}
