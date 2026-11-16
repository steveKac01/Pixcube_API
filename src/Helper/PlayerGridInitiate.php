<?php

namespace App\Helper;

use App\Game\CubeState;

class PlayerGridInitiate
{

    /**
     * Initiate player grid according to the puzzle solution grid.
     * All cubes of the player grid are Intact.
     * @param array $solutionGrid
     * @return array
     */
    public function compute(array $solutionGrid): array
    {
        $size = [];
        $size['x'] = count($solutionGrid);
        $size['y'] = count($solutionGrid[0] ?? []);
        $size['z'] = count($solutionGrid[0][0] ?? []);

        if (0 === $size['x'] || 0 === $size['y'] || 0 === $size['z']) {
            throw new \InvalidArgumentException('The puzzle grid is invalid.');
        }

        $playerGrid = [];

        for ($x = 0; $x < $size['x']; $x++) {
            for ($y = 0; $y < $size['y']; $y++) {
                for ($z = 0; $z < $size['z']; $z++) {
                    $playerGrid[$x][$y][$z] = CubeState::INTACT;
                }
            }
        }

        return $playerGrid;
    }
}
