<?php

namespace App\Dto\Game;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Player's move, the coordinates (x,y,z) of the cube targeted in the object grid.
 */
final readonly class PlayerMoveRequest
{
    public function __construct(
        #[Assert\PositiveOrZero()]
        public int $x,
        #[Assert\PositiveOrZero()]
        public int $y,
        #[Assert\PositiveOrZero()]
        public int $z
    ) {}
}
