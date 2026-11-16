<?php

namespace App\Tests\Service;

use App\Game\CubeState;
use App\Enum\MoveResult;
use PHPUnit\Framework\TestCase;
use App\Service\DestroyCube;
use App\Tests\Support\TutorialGrid;
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

        (new DestroyCube())->compute(TutorialGrid::load(), $this->initialPlayerGrid(), 0, $playerMove);
    }

    public function testPlayerDestroyAnEmptyCube(): void
    {
        $currentErrorCount = 0;
        $x = 0;
        $y = 0;
        $z = 0;
        $cubeDestroyed = ['x' => $x, 'y' => $y, 'z' => $z];

        $outcomeResult = (new DestroyCube())->compute(TutorialGrid::load(), $this->initialPlayerGrid(), $currentErrorCount, $cubeDestroyed);
        $this->assertSame(MoveResult::OK, $outcomeResult->moveResult);
        $this->assertSame(CubeState::DESTROYED, $outcomeResult->playerGrid[$x][$y][$z]);
        $this->assertSame($currentErrorCount, $outcomeResult->errorsCount);
    }

    public function testPlayerDestroyASolutionCube(): void
    {
        $currentErrorCount = 0;
        $x = 0;
        $y = 0;
        $z = 1;
        $cubeDestroyed = ['x' => $x, 'y' => $y, 'z' => $z];

        $outcomeResult = (new DestroyCube())->compute(TutorialGrid::load(), $this->initialPlayerGrid(), $currentErrorCount, $cubeDestroyed);
        $this->assertSame(MoveResult::ERROR, $outcomeResult->moveResult);
        $this->assertSame(CubeState::LOCKED, $outcomeResult->playerGrid[$x][$y][$z]);
        $this->assertSame($currentErrorCount + 1, $outcomeResult->errorsCount);
    }

    public function testPLayerMakeThirdErrorAndGetGameOver(): void
    {
        $currentErrorCount = 2;
        $x = 0;
        $y = 0;
        $z = 1;
        $cubeDestroyed = ['x' => $x, 'y' => $y, 'z' => $z];

        $outcomeResult = (new DestroyCube())->compute(TutorialGrid::load(), $this->initialPlayerGrid(), $currentErrorCount, $cubeDestroyed);
        $this->assertSame(MoveResult::GAMEOVER, $outcomeResult->moveResult);
        $this->assertSame(CubeState::LOCKED, $outcomeResult->playerGrid[$x][$y][$z]);
        $this->assertSame($currentErrorCount + 1, $outcomeResult->errorsCount);
    }

    public function testPlayerDestroySameEmptyCubeTwice(): void
    {
        $currentErrorCount = 0;
        $x = 0;
        $y = 0;
        $z = 0;
        $cubeDestroyed = ['x' => $x, 'y' => $y, 'z' => $z];

        $firstOutcomeResult = (new DestroyCube())->compute(TutorialGrid::load(), $this->initialPlayerGrid(), $currentErrorCount, $cubeDestroyed);
        $secondOutcomeResult = (new DestroyCube())->compute(TutorialGrid::load(), $firstOutcomeResult->playerGrid, $firstOutcomeResult->errorsCount, $cubeDestroyed);
        $this->assertSame(MoveResult::OK, $secondOutcomeResult->moveResult);
        $this->assertSame(CubeState::DESTROYED,  $secondOutcomeResult->playerGrid[$x][$y][$z]);
        $this->assertSame($currentErrorCount,  $secondOutcomeResult->errorsCount); // player not penalized
    }

    public function testPlayerDestroySameSolutionCubeTwice(): void
    {
        $currentErrorCount = 0;
        $x = 0;
        $y = 0;
        $z = 1;
        $cubeDestroyed = ['x' => $x, 'y' => $y, 'z' => $z];

        $firstOutcomeResult = (new DestroyCube())->compute(TutorialGrid::load(), $this->initialPlayerGrid(), $currentErrorCount, $cubeDestroyed);
        $secondOutcomeResult = (new DestroyCube())->compute(TutorialGrid::load(), $firstOutcomeResult->playerGrid, $firstOutcomeResult->errorsCount, $cubeDestroyed);
        $this->assertSame(MoveResult::ERROR, $secondOutcomeResult->moveResult);
        $this->assertSame(CubeState::LOCKED,  $secondOutcomeResult->playerGrid[$x][$y][$z]);
        $this->assertSame($currentErrorCount + 1,  $secondOutcomeResult->errorsCount); // player is penalized only one time
    }

    public function testPlayerDestroyLastEmptyCubeAndGetVictory(): void
    {
        $solutionGrid = TutorialGrid::load();
        $currentErrorCount = 0;
        $x = 0;
        $y = 0;
        $z = 0;
        $cubeDestroyed = ['x' => $x, 'y' => $y, 'z' => $z];

        $outcomeResult = (new DestroyCube())->compute(
            $solutionGrid,
            $this->playerGridOneClickToVictory($solutionGrid, $cubeDestroyed),
            $currentErrorCount,
            $cubeDestroyed
        );

        $this->assertSame(MoveResult::VICTORY, $outcomeResult->moveResult);
        $this->assertSame($currentErrorCount, $outcomeResult->errorsCount);
    }

    /**
     * The player grid, same size as tutorial grid (4 x 7 x 3).
     *
     * @return list<list<list<int>>>
     */
    private function initialPlayerGrid(): array
    {
        return array_fill(0, 4, array_fill(0, 7, array_fill(0, 3, CubeState::INTACT)));
    }

    private function playerGridOneClickToVictory(array $solutionGrid, array $lastCube): array
    {
        $playerGrid = [];

        foreach ($solutionGrid as $x => $slice) {
            foreach ($slice as $y => $row) {
                foreach ($row as $z => $ceil) {

                    $playerGrid[$x][$y][$z] = null === $ceil ? CubeState::DESTROYED : CubeState::INTACT;
                }
            }
        }

        //reset the first cube to let win the player on his next move
        $playerGrid[$lastCube['x']][$lastCube['y']][$lastCube['z']] = CubeState::INTACT;

        return $playerGrid;
    }
}
