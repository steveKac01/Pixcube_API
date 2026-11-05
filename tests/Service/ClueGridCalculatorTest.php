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
        $grid = array_map(static fn ($cell) => [[$cell]], $cells);

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
}
