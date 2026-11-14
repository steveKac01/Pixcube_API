<?php

namespace App\Game;

use App\Enum\MoveResult;

/**
 * @param list<list<list<int>>> $playerGrid the player's grid after the move
 */
class MoveOutCome
{
    public function __construct(public MoveResult $moveResult, public array $playerGrid, public int $errorsCount) {}
}
