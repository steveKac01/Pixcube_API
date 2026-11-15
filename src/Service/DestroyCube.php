<?php

namespace App\Service;

use App\Enum\MoveResult;
use App\Game\MoveOutCome;
use App\Game\CubeState;
use App\Helper\SizeGridCalculator;

class DestroyCube
{

    private const MAX_ERRORS = 3;

    /**
     * Undocumented function
     *
     * @param list<list<list<string|null>>> $solutionGrid grid indexed [x][y][z]: null is an empty cell, a string is a cube color
     * @param  list<list<list<int>>> $playerGrid
     * @param integer $errorsCount
     * @param array{x:int, y:int, z:int} $cubeSelected
     * @param array $cubeSelected
     * @return MoveOutCome
     */
    public function compute(array $solutionGrid, array $playerGrid, int $errorsCount,  array $cubeSelected): MoveOutCome
    {
        // Check if the move is not OOB from the solution grid
        $gridSize = (new SizeGridCalculator())->compute($solutionGrid);
        ['x' => $x, 'y' => $y, 'z' => $z] = $cubeSelected;
        
        if ($x < 0 || $x >= $gridSize['x'] || $y < 0 || $y >= $gridSize['y'] || $z < 0 || $z >= $gridSize['z']) {
            throw new \InvalidArgumentException('The cube selected is out of bound.');
        }

        // Apply game destroy cube rules
        //The player did a good move
        if (null === $solutionGrid[$x][$y][$z]) {
            $playerGrid[$x][$y][$z] = CubeState::DESTROYED;

            return new MoveOutCome(
                $this->isVictory($solutionGrid, $playerGrid) ?
                    MoveResult::VICTORY : MoveResult::OK,
                $playerGrid,
                $errorsCount
            );
        }

        // The move was already done, return the same moveResult without incrementing errorCount (player not penalized)
        $cell = $playerGrid[$x][$y][$z];
        if ($cell !== CubeState::INTACT) {
            return  new MoveOutCome(
                CubeState::DESTROYED === $cell ?
                    moveResult::OK : MoveResult::ERROR,
                $playerGrid,
                $errorsCount
            );
        }

        // Wrong move of the player, the cube is now locked
        $playerGrid[$x][$y][$z] = CubeState::LOCKED;
        $errorsCount++;
        return new MoveOutCome(
            $errorsCount >= self::MAX_ERRORS ? MoveResult::GAMEOVER : moveResult::ERROR,
            $playerGrid,
            $errorsCount
        );
    }

    /**
     * The player wins when every empty cell of the solution has been destroyed.
     */
    private function isVictory(array $solutionGrid, array $playerGrid): bool
    {
        foreach ($solutionGrid as $x => $slice) {
            foreach ($slice as $y => $row) {
                foreach ($row as $z => $cell) {
                    if (null === $cell && CubeState::DESTROYED !== $playerGrid[$x][$y][$z]) {
                        return false;
                    }
                }
            }
        }

        return true;
    }
}
