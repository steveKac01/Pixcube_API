<?php

namespace App\Tests\Mapper;

use App\Dto\Game\GameResponse;
use App\Entity\Category;
use App\Entity\GameSession;
use App\Entity\Puzzle;
use App\Enum\GameStatus;
use App\Helper\PlayerGridInitiate;
use App\Mapper\GameResponseMapper;
use App\Service\ClueGridCalculator;
use PHPUnit\Framework\TestCase;
use App\Tests\Support\CreateGameSession;
use App\Tests\Support\CreatePuzzle;
use App\Tests\Support\CreateUser;
use App\Tests\Support\TutorialGrid;
use Symfony\Component\Uid\Uuid;


class GameResponseMapperTest extends TestCase
{
    use CreateGameSession, CreatePuzzle, CreateUser;

    public function testMapperReturnsValidDto(): void
    {
        $game = $this->createSession();
        $dto = (new GameResponseMapper(new ClueGridCalculator()))->map($game);

        $this->assertInstanceOf(GameResponse::class, $dto);
        $this->assertSame($game->getId()->toRfc4122(), $dto->gameId);
        $this->assertSame($game->getPuzzle()->getId(), $dto->puzzleId);
        $this->assertSame($game->getStatus(), $dto->status);
        $this->assertSame($game->getErrorsCount(), $dto->errorsCount);
        $this->assertSame($game->getPlayerGrid(), $dto->playerGrid);
    }

    private function createSession(): GameSession
    {
        $category = (new Category())->setName('Tutorial')
            ->setSlug('tutorial')
            ->setDescription('description');
        $this->setId($category, 1);

        $puzzle = (new Puzzle())->setCategory($category)
            ->setDifficulty(0)
            ->setIsPublished(true)
            ->setName('radio')
            ->setObjectGrid(TutorialGrid::load());
        $this->setId($puzzle, 1);

        $gameSession = (new GameSession())
            ->setStatus(GameStatus::PLAYING)
            ->setPuzzle($puzzle)
            ->setErrorsCount(0)
            ->setPlayerGrid((new PlayerGridInitiate())->compute($puzzle->getObjectGrid()));

        $this->setId($gameSession, Uuid::v4());


        return $gameSession;
    }

    private function setId(object $entity, int|string|Uuid $id): void
    {
        $reflection = new \ReflectionProperty($entity::class, 'id');
        $reflection->setValue($entity, $id);
    }
}
