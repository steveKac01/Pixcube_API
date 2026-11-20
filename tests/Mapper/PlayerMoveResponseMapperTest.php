<?php

namespace App\Tests\Mapper;

use App\Dto\Game\PlayerVictoryMoveResponse;
use App\Entity\GameSession;
use App\Entity\Puzzle;
use App\Enum\GameStatus;
use App\Enum\MoveResult;
use App\Mapper\PlayerMoveResponseMapper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PlayerMoveResponseMapperTest extends TestCase
{
    /**
     * @return iterable<string, array{MoveResult, GameStatus}>
     */
    public static function movesWithoutVictory(): iterable
    {
        yield 'ok' => [MoveResult::OK, GameStatus::PLAYING];
        yield 'error' => [MoveResult::ERROR, GameStatus::PLAYING];
        yield 'game over' => [MoveResult::GAMEOVER, GameStatus::GAMEOVER];
    }

    #[DataProvider('movesWithoutVictory')]
    public function testOnlyAVictoryRevealsTheObject(MoveResult $result, GameStatus $status): void
    {
        $response = (new PlayerMoveResponseMapper())->map($result, $this->game($status));

        $this->assertNotInstanceOf(PlayerVictoryMoveResponse::class, $response);
        $this->assertSame($result, $response->result);
    }

    public function testVictoryRevealsTheObject(): void
    {
        $response = (new PlayerMoveResponseMapper())->map(MoveResult::VICTORY, $this->game(GameStatus::VICTORY));

        $this->assertInstanceOf(PlayerVictoryMoveResponse::class, $response);
        $this->assertSame('une radio', $response->name);
        $this->assertSame([[['#ff0000']]], $response->objectGrid);
    }

    private function game(GameStatus $status): GameSession
    {
        $puzzle = (new Puzzle())->setName('une radio')->setObjectGrid([[['#ff0000']]]);

        return (new GameSession())->setPuzzle($puzzle)->setStatus($status);
    }
}
