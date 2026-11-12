<?php

namespace App\Service;

/**
 * Turns a puzzle solution grid into the clues shown to the player.
 *
 * The grid is indexed as $grid[x][y][z]: an empty cell is null, a cube is its hex color.
 * Each line of cells along an axis gets one clue:
 *  - count:  how many cubes the line contains
 *  - groups: how many separate groups of cubes it contains, capped at 3 (3 means three or more)
 *
 * Clues are indexed by the two other coordinates:
 *  - x[y][z] for the lines along X
 *  - y[x][z] for the lines along Y
 *  - z[x][y] for the lines along Z
 */
class ClueGridCalculator
{

    private const MAX_GROUPS = 3;

    /**
     * @param list<list<list<string|null>>> $grid Solution grid indexed [x][y][z]: null is an empty cell, a string is a cube color
     *
     * @return array{x: list<list<array{count: int, groups: int}>>, y: list<list<array{count: int, groups: int}>>, z: list<list<array{count: int, groups: int}>>}
     */
    public function compute(array $grid): array
    {
        // Puzzle grid validity check
        $sizeX = count($grid);
        $sizeY = count($grid[0] ?? []);
        $sizeZ = count($grid[0][0] ?? []);

        if (0 === $sizeX || 0 === $sizeY || 0 === $sizeZ) {
            throw new \InvalidArgumentException('The puzzle grid is invalid.');
        }

        $clues = ['x' => [], 'y' => [], 'z' => []];

        // Lines along X axis: one line per (y,z)
        for ($y = 0; $y < $sizeY; ++$y) {
            for ($z = 0; $z < $sizeZ; ++$z) {
                $line = [];
                for ($x = 0; $x < $sizeX; ++$x) {
                    $line[] = $grid[$x][$y][$z];
                }
                $clues['x'][$y][$z] = $this->analyseLine($line);
            }
        }


        // Lines along Y axis: one line per (x,z)
        for ($x = 0; $x < $sizeX; ++$x) {
            for ($z = 0; $z < $sizeZ; ++$z) {
                $line = [];
                for ($y = 0; $y < $sizeY; $y++) {
                    $line[] = $grid[$x][$y][$z];
                }
                $clues['y'][$x][$z] = $this->analyseLine($line);
            }
        }


        // Lines along Z axis: one line per (x,y), already stored as a line in the grid
        for ($x = 0; $x < $sizeX; ++$x) {
            for ($y = 0; $y < $sizeY; ++$y) {
                $clues['z'][$x][$y] = $this->analyseLine($grid[$x][$y]);
            }
        }

        return $clues;
    }

    /**
     * Counts the cubes and groups in a line.
     * @param list<string|null> $line
     *
     * @return array{count: int, groups: int}
     */
    private function analyseLine(array $line): array
    {

        $count = 0;
        $groups = 0;
        $previousWasCube = false; //By default false when starts a line

        foreach ($line as $cell) {
            $isCube = null !== $cell;

            if ($isCube) {
                ++$count;

                // A cube that follows an empty cell opens a new group
                if (!$previousWasCube) {
                    ++$groups;
                }
            }

            $previousWasCube = $isCube;
        }

        return ['count' => $count, 'groups' => min($groups, self::MAX_GROUPS)];
    }
}
