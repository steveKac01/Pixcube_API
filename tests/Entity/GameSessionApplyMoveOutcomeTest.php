<?php

namespace App\Tests\Entity;

use App\Entity\GameSession;
use App\Enum\GameStatus;
use App\Enum\MoveResult;
use App\Game\MoveOutcome;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;



class GameSessionapplyMoveOutcomeTest extends TestCase
{

    /**
     * @return iterable<string, array{MoveResult, GameStatus, bool}>
     */
    public static function moveResults(): iterable
    {
        yield 'ok keeps the game going' => [MoveResult::OK, GameStatus::PLAYING, false];
        yield 'error keeps the game going' => [MoveResult::ERROR, GameStatus::PLAYING, false];
        yield 'game over ends the game' => [MoveResult::GAMEOVER, GameStatus::GAMEOVER, true];
        yield 'victory ends the game' => [MoveResult::VICTORY, GameStatus::VICTORY, true];
    }

    #[DataProvider('moveResults')]
    public function testStatusAndEndDateFollowTheMoveResult(MoveResult $result, GameStatus $status, bool $isFinished): void
    {
        $gameSession = new GameSession();

        $gameSession->applyMoveOutcome(new MoveOutcome($result, [[[0]]], 0));

        $this->assertSame($status, $gameSession->getStatus());
        $this->assertSame($isFinished, null !== $gameSession->getFinishedAt());
    }

    public function testSessionGetFullyUpdated(): void
    {
        $errorCount = 1;
        $playerGrid = [[[1]]];
        $gameSession = new GameSession();

        $moveResult = new MoveOutcome(MoveResult::VICTORY, $playerGrid, $errorCount);

        $gameSession->applyMoveOutcome($moveResult);

        $this->assertSame(GameStatus::VICTORY, $gameSession->getStatus());
        $this->assertSame($errorCount, $gameSession->getErrorsCount());
        $this->assertSame($playerGrid, $gameSession->getPlayerGrid());
        $this->assertSame(true, null !== $gameSession->getFinishedAt());
    }
}
