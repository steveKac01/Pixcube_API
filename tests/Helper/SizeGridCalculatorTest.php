<?php

namespace App\tests\Helper;

use App\Helper\SizeGridCalculator;
use App\Tests\Support\TutorialGrid;
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
        $size = (new SizeGridCalculator())->compute(TutorialGrid::load());

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
}
