<?php

namespace App\Game;

/**
 * State of a cube in the player's grid. Same values are used in the front.
 */
final class CubeState
{
    public const DESTROYED = 0;
    public const INTACT = 1;
    public const LOCKED = 3;
}
