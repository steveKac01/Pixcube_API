<?php

namespace App\Tests\Helpers;

use App\Game\CubeState;
use App\Helper\PlayerGridInitiate;
use App\Tests\Support\TutorialGrid;
use PHPUnit\Framework\TestCase;

class PlayerGridInitiateTest extends TestCase
{
    public function testPlayerGridCeilsAreAllIntactWithTutorialGridSolution(): void
    {
        $error = false;

        $solutionGrid = TutorialGrid::load();
        $playerGrid = (new PlayerGridInitiate())->compute($solutionGrid);

        foreach ($playerGrid as $slide) {
            foreach ($slide as $row) {
                foreach ($row as $ceil) {
                    if (CubeState::INTACT !== $ceil) {
                        $error = true;
                        break;
                    }
                }
            }
        }

        $this->assertSame(false, $error);
    }

    public function testPlayerGridRejectedWithIncompleteGridSolution(): void
    {
        $solutionGrid = [[[]]];

        $this->expectException(\InvalidArgumentException::class);

        (new PlayerGridInitiate())->compute($solutionGrid);
    }
}
