<?php

namespace App\Helper;

class SizeGridCalculator
{
    /**
     * Calculate the size on each axes of the grid.
     *
     * @param array $grid grid indexed [x][y][z]
     * @return array
     */
    public function compute(array $grid): array
    {

        $size = [];
        $size['x'] = count($grid);
        $size['y'] = count($grid[0]);
        $size['z'] = count($grid[0][0]);

        return $size;
    }
}
