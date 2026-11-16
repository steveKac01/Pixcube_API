<?php

namespace App\tests\Helper;

use App\Helper\SizeGridCalculator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class sizeGridCalculatorTest extends TestCase
{

    /**
     * @param array<mixed> $grid
     */
    #[DataProvider('emptyGrids')]
    public function testAnEmptyGridIsRejected(array $grid): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new SizeGridCalculator())->compute($grid);
    }

    public function testReturnExactGridSizeValueForTheTutorialGrid()
    {

        $size = (new SizeGridCalculator())->compute($this->tutorialGrid());

        $this->assertSame(4, $size['x']);
        $this->assertSame(7, $size['y']);
        $this->assertSame(3, $size['z']);
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
     * The tutorial object of the front (4 x 7 x 3), to check the service with real data.
     *
     * @return list<list<list<string|null>>>
     */
    public function tutorialGrid(): array
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
