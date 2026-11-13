<?php

namespace App\Tests\Service;

use PHPUnit\Framework\TestCase;
use App\Service\DestroyCube;
use PHPUnit\Framework\Attributes\DataProvider;


class DestroyCubeTest extends TestCase
{
    /**
     * @return iterable<string, array{array{x:int, y:int, z:int}}>
     */
    public static function outOfBoundMoves(): iterable
    {
        yield 'x just after the last slice' => [['x' => 4, 'y' => 0, 'z' => 0]];
        yield 'y just after the last row' => [['x' => 0, 'y' => 7, 'z' => 0]];
        yield 'z just after the last cell' => [['x' => 0, 'y' => 0, 'z' => 3]];
        yield 'negative x' => [['x' => -1, 'y' => 0, 'z' => 0]];
        yield 'negative y' => [['x' => 0, 'y' => -1, 'z' => 0]];
        yield 'negative z' => [['x' => 0, 'y' => 0, 'z' => -1]];
        yield 'far away' => [['x' => 10, 'y' => 7, 'z' => 2]];
    }

    /**
     * @param array{x:int, y:int, z:int} $playerMove
     */
    #[DataProvider('outOfBoundMoves')]
    public function testPlayerMoveOutOfBoundReject(array $playerMove): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new DestroyCube())->compute($this->tutorialGrid(), $playerMove);
    }

    /**
     * The tutorial object of the front (4 x 7 x 3), to check the service with real data.
     *
     * @return list<list<list<string|null>>>
     */
    private function tutorialGrid(): array
    {
        $_ = null;
        $b = '#feee9f';
        $x = '#000000';
        $g = '#696969';
        $l = '#a6a6a6';
        $z = '#858585';
        $r = '#fa0000';

        return [
            [
                [$_, $b, $b],
                [$_, $b, $b],
                [$_, $b, $b],
                [$_, $b, $b],
                [$_, $b, $b],
                [$_, $r, $_],
                [$_, $_, $_],
            ],
            [
                [$_, $b, $b],
                [$x, $b, $g],
                [$x, $b, $l],
                [$x, $b, $g],
                [$_, $b, $b],
                [$_, $_, $_],
                [$_, $_, $_],
            ],
            [
                [$_, $b, $b],
                [$x, $b, $g],
                [$x, $b, $l],
                [$x, $b, $g],
                [$_, $b, $b],
                [$_, $_, $_],
                [$_, $_, $_],
            ],
            [
                [$_, $b, $b],
                [$_, $b, $b],
                [$_, $b, $b],
                [$_, $b, $b],
                [$_, $b, $b],
                [$_, $z, $_],
                [$_, $z, $_],
            ],
        ];
    }
}
