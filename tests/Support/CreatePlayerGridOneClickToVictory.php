<?php

namespace App\Tests\Support;

use App\Game\CubeState;

trait CreatePlayerGridOneClickToVictory
{

    private function createPlayerGridOneClickToVictory(array $solutionGrid, array $lastCube): array
    {
        $playerGrid = [];

        foreach ($solutionGrid as $x => $slice) {
            foreach ($slice as $y => $row) {
                foreach ($row as $z => $ceil) {

                    $playerGrid[$x][$y][$z] = null === $ceil ? CubeState::DESTROYED : CubeState::INTACT;
                }
            }
        }

        //reset the first cube to let win the player on his next move
        $playerGrid[$lastCube['x']][$lastCube['y']][$lastCube['z']] = CubeState::INTACT;

        return $playerGrid;
    }
}
