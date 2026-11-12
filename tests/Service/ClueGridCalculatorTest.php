<?php

namespace App\Tests\Service;

use App\Service\ClueGridCalculator;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class ClueGridCalculatorTest extends TestCase
{
    private const COLOR = '#ff0000';

    /**
     * @return iterable<string, array{list<string|null>, int, int}>
     */
    public static function lines(): iterable
    {
        $c = self::COLOR;

        yield 'empty line' => [[null, null, null], 0, 0];
        yield 'single cube' => [[$c], 1, 1];
        yield 'full line' => [[$c, $c, $c, $c], 4, 1];
        yield 'empty cells around a group are ignored' => [[null, $c, $c, null], 2, 1];
        yield 'two groups at the edges' => [[$c, null, null, $c], 2, 2];
        yield 'two groups' => [[$c, $c, null, $c], 3, 2];
        yield 'three groups' => [[$c, null, $c, null, $c], 3, 3];
        yield 'four groups are capped at three' => [[$c, null, $c, null, $c, null, $c], 4, 3];
        yield 'colors do not split a group' => [[$c, '#00ff00', $c], 3, 1];
    }

    /**
     * @param list<string|null> $cells
     */
    #[DataProvider('lines')]
    public function testLineClue(array $cells, int $count, int $groups): void
    {
        // A line of cells along X is a grid of size n x 1 x 1
        $grid = array_map(static fn($cell) => [[$cell]], $cells);

        $clue = (new ClueGridCalculator())->compute($grid)['x'][0][0];

        $this->assertSame(['count' => $count, 'groups' => $groups], $clue);
    }

    public function testEachAxisIsIndexedByTheTwoOtherCoordinates(): void
    {
        // An asymmetric grid catches mixed-up axes that a cube would hide
        $grid = array_fill(0, 2, array_fill(0, 3, array_fill(0, 4, null))); // 2 x 3 x 4
        $grid[1][2][3] = self::COLOR;

        $clues = (new ClueGridCalculator())->compute($grid);

        $this->assertCount(3, $clues['x']);    // one row per y
        $this->assertCount(4, $clues['x'][0]); // one clue per z
        $this->assertCount(2, $clues['y']);    // one row per x
        $this->assertCount(4, $clues['y'][0]); // one clue per z
        $this->assertCount(2, $clues['z']);    // one row per x
        $this->assertCount(3, $clues['z'][0]); // one clue per y

        // The only cube sits on the line (y=2, z=3) along X, (x=1, z=3) along Y, (x=1, y=2) along Z
        $this->assertSame(1, $clues['x'][2][3]['count']);
        $this->assertSame(1, $clues['y'][1][3]['count']);
        $this->assertSame(1, $clues['z'][1][2]['count']);

        // Neighbouring lines stay empty
        $this->assertSame(0, $clues['x'][0][0]['count']);
        $this->assertSame(0, $clues['y'][0][3]['count']);
        $this->assertSame(0, $clues['z'][0][2]['count']);
    }

    /**
     * @return iterable<string, array{array<mixed>}>
     */
    public static function emptyGrids(): iterable
    {
        yield 'no x' => [[]];
        yield 'no y' => [[[]]];
        yield 'no z' => [[[[]]]];
    }

    /**
     * @param array<mixed> $grid
     */
    #[DataProvider('emptyGrids')]
    public function testAnEmptyGridIsRejected(array $grid): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new ClueGridCalculator())->compute($grid);
    }

    public function testCluesOfTheTutorialObject(): void
    {
        $clues = (new ClueGridCalculator())->compute($this->tutorialGrid());

        // Along X, through (y=1, z=0): [empty, black, black, empty]
        $this->assertSame(['count' => 2, 'groups' => 1], $clues['x'][1][0]);
        // Along X, through (y=5, z=1): [red, empty, empty, grey] = two separate groups
        $this->assertSame(['count' => 2, 'groups' => 2], $clues['x'][5][1]);
        // Along Y, through (x=0, z=1): six cubes, then an empty cell
        $this->assertSame(['count' => 6, 'groups' => 1], $clues['y'][0][1]);
        // Along Y, through (x=3, z=1): seven cubes, the whole line
        $this->assertSame(['count' => 7, 'groups' => 1], $clues['y'][3][1]);
        // Along Z, through (x=0, y=5): [empty, red, empty]
        $this->assertSame(['count' => 1, 'groups' => 1], $clues['z'][0][5]);
        // Along Z, through (x=0, y=6): nothing at all
        $this->assertSame(['count' => 0, 'groups' => 0], $clues['z'][0][6]);
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
