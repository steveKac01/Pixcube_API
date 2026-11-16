<?php

namespace App\Dto\Game;

use App\Enum\GameStatus;

/**
 * What the API sends back when a game is created: everything the front needs to build the board.
 */
final readonly class GameResponse
{
    /**
     * @param array<string, mixed>  $clues      numbers shown on the cubes, see ClueCalculator
     * @param list<list<list<int>>> $playerGrid state of each cube (0 destroyed, 1 intact, 3 locked)
     */
    public function __construct(
        public string $gameId,
        public int $puzzleId,
        public GameStatus $status,
        public int $errorsCount,
        public array $clues,
        public array $playerGrid,
    ) {}
}
