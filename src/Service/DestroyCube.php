<?php

namespace App\Service;

class DestroyCube
{

    /**
     * @param list<list<list<string|null>>> $solutionGrid grid indexed [x][y][z]: null is an empty cell, a string is a cube color
     * @param array{x:int, y:int, z:int} $cubeSelected
     */
    public function compute(array $solutionGrid,  array $cubeSelected)
    {

        // Check if the move is not OOB from the solution grid
        $sizeX = count($solutionGrid);
        $sizeY = count($solutionGrid[0]);
        $sizeZ = count($solutionGrid[0][0]);

        if ($cubeSelected['x'] < 0 || $cubeSelected['x'] >= $sizeX || $cubeSelected['y'] < 0 || $cubeSelected['y'] >= $sizeY || $cubeSelected['z'] < 0 || $cubeSelected['z'] >= $sizeZ) {
            throw new \InvalidArgumentException('The cube selected is out of bound.');
        }



        // Apply game destroy cube rules

        // Is victory ?

        // Return moveOutCome
    }
}
