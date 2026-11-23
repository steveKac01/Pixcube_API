<?php

namespace App\Tests\Controller;

use App\Entity\GameSession;
use App\Entity\User;
use App\Enum\GameStatus;
use App\Game\CubeState;
use App\Helper\PlayerGridInitiate;
use App\Tests\Support\CreatePlayerGridOneClickToVictory;
use App\Tests\Support\CreateGameSession;
use App\Tests\Support\CreatePuzzle;
use App\Tests\Support\CreateUser;
use App\Tests\Support\TutorialGrid;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Uid\Uuid;

final class PlayerMoveControllerTest extends WebTestCase
{
    use CreateUser, CreatePuzzle, CreateGameSession, CreatePlayerGridOneClickToVictory;
    private KernelBrowser $client;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
    }

    private function request(string $gameSessionId, array $moveCoordinate, ?user $user = null,): void
    {
        $headers = $user ? ['HTTP_AUTHORIZATION' => 'Bearer ' . $this->createToken($user)] : [];
        $this->client->jsonRequest('POST', '/api/games/' . $gameSessionId . '/moves', $moveCoordinate, server: $headers);
    }

    private function jsonResponse(): array
    {
        return json_decode($this->client->getResponse()->getContent(), true);
    }

    public function testControllerRejectGetMethod(): void
    {
        $gameSession = $this->createGame();
        $this->client->jsonRequest('GET', '/api/games/' . $gameSession->getId() . '/moves', TutorialGrid::$validMove);

        $this->assertResponseStatusCodeSame(405);
    }

    public static function invalidGameSession(): iterable
    {
        yield 'Invalid game session id' => ['1'];
        yield 'Invalid game session id with text' => ['text'];
        yield 'game session with unknown id' =>  [Uuid::v4()->toRfc4122()];
    }

    #[DataProvider('invalidGameSession')]
    public function testInvalidGameSessionId(string $id): void
    {
        $this->request($id, TutorialGrid::$validMove);

        $this->assertResponseStatusCodeSame(404);
    }

    public static function invalidPlayerMoves(): iterable
    {
        yield 'empty array' => [[]];
        yield 'negative coordinate X ' => [['x' => -1, 'y' => 1, 'z' => 1]];
        yield 'negative coordinate y ' => [['x' => 0, 'y' => -1, 'z' => 1]];
        yield 'negative coordinate z ' => [['x' => 0, 'y' => 1, 'z' => -1]];
        yield 'invalid type coordinate x ' => [['x' => 'a', 'y' => 1, 'z' => -1]];
        yield 'missing coordinate x ' => [['x' => '1', 'y' => 1]];
        yield 'out of bound coordinate' => [['x' => 20, 'y' => 1, 'z' => 1]];
    }

    #[DataProvider('invalidPlayerMoves')]
    public function testWrongPlayerMoveRequest(array $playerMove): void
    {
        $gameSession = $this->createGame();

        $this->request($gameSession->getId(), $playerMove);
        $this->assertResponseStatusCodeSame(422);

        // Nothing changes in bdd
        $saved = $this->reload($gameSession);
        $this->assertSame($gameSession->getErrorsCount(), $saved->getErrorsCount());
        $this->assertSame($gameSession->getPlayerGrid(), $saved->getPlayerGrid());
    }

    public function testUserGuestCanPlayTutorialGrid(): void
    {
        $gameSession = $this->createGame();
        $this->request($gameSession->getId(), TutorialGrid::$validMove);

        $this->assertResponseStatusCodeSame(200);
    }

    public function testGuestCannotPlayAGameThatBelongsToAUser(): void
    {
        $gameSession = $this->createGame($this->createUser());

        $this->request($gameSession->getId(), TutorialGrid::$validMove);
        $this->assertResponseStatusCodeSame(401);
    }

    public function testLoggedUserCanPlayAGuestGame(): void
    {
        $gameSession = $this->createGame();

        $this->request($gameSession->getId(), TutorialGrid::$validMove, $this->createUser());
        $this->assertResponseStatusCodeSame(200);
    }

    public function testOwnerCanPlayTheirGame(): void
    {
        $user = $this->createUser();
        $gameSession = $this->createGame($user, category: 'Puzzle test');

        $this->request($gameSession->getId(), TutorialGrid::$validMove, $user);
        $this->assertResponseStatusCodeSame(200);
    }

    public function testUserCannotPlayAnotherUsersGame(): void
    {
        $user = $this->createUser();
        $userOwningGameSession = $this->createUser('another@email.fr');

        $gameSession = $this->createGame($userOwningGameSession, category: 'Puzzle test');

        $this->request($gameSession->getId(), TutorialGrid::$validMove, $user);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testMoveOnAFinishedGameIsRejected(): void
    {
        $gameSession = $this->createGame(status: GameStatus::VICTORY);

        $this->request($gameSession->getId(), TutorialGrid::$validMove);
        $this->assertResponseStatusCodeSame(409);
    }

    public function testGameStatusOkGiveValidResponse(): void
    {
        $errorsCount = 1;

        $gameSession = $this->createGame(errorsCount: $errorsCount);

        $this->request($gameSession->getId(), TutorialGrid::$validMove);
        $response = $this->jsonResponse();

        $this->assertArrayHasKey('result', $response);
        $this->assertArrayHasKey('errorsCount', $response);
        $this->assertSame($errorsCount, $response['errorsCount']);
        $this->assertSame('ok', $response['result']);
    }

    public function testGameStatusErrorGiveValidResponse(): void
    {
        $errorsCount = 1;

        $gameSession = $this->createGame(errorsCount: $errorsCount);

        $this->request($gameSession->getId(), TutorialGrid::$errorMove);
        $response = $this->jsonResponse();

        $this->assertArrayHasKey('result', $response);
        $this->assertArrayHasKey('errorsCount', $response);
        $this->assertSame($errorsCount + 1, $response['errorsCount']);
        $this->assertSame('error', $response['result']);
    }

    public function testGameStatusGameOverGiveValidResponse(): void
    {
        $errorsCount = 2;

        $gameSession = $this->createGame(errorsCount: $errorsCount);

        $this->request($gameSession->getId(), TutorialGrid::$errorMove);
        $response = $this->jsonResponse();

        $this->assertArrayHasKey('result', $response);
        $this->assertArrayHasKey('errorsCount', $response);
        $this->assertSame($errorsCount + 1, $response['errorsCount']);
        $this->assertSame('game-over', $response['result']);
    }

    public static function movesWithoutVictory(): iterable
    {
        yield 'ok' => [['x' => 0, 'y' => 0, 'z' => 0], 0];
        yield 'error' => [['x' => 1, 'y' => 1, 'z' => 1], 0];
        yield 'game over' => [['x' => 1, 'y' => 1, 'z' => 1], 2];
    }

    #[DataProvider('movesWithoutVictory')]
    public function testNoMoveButTheWinningOneRevealsTheSolution(array $move, int $errorsCount): void
    {
        $gameSession = $this->createGame(errorsCount: $errorsCount);

        $this->request($gameSession->getId(), $move);
        $response = $this->jsonResponse();
        $rawResponse = $this->client->getResponse()->getContent();

        // Important ! No solution leak
        $this->assertArrayNotHasKey('name', $response);
        $this->assertArrayNotHasKey('objectGrid', $response);
        $this->assertStringNotContainsString($gameSession->getPuzzle()->getName(), $rawResponse);
        $this->assertStringNotContainsString('#', $rawResponse); // the solution is the only place with hex colors
    }

    public function testVictoryResponseOnPlayerMove(): void
    {
        $puzzle = $this->createPuzzle('Puzzle test', TutorialGrid::load(), $this->em);
        $playerGrid = $this->createPlayerGridOneClickToVictory($puzzle->getObjectGrid(), TutorialGrid::$validMove);
        $gameSession = $this->createGameSession($puzzle, $this->em, null, $playerGrid);

        $gameSession = $this->createGame(playerGrid: $this->createPlayerGridOneClickToVictory($puzzle->getObjectGrid(), TutorialGrid::$validMove));

        $this->request($gameSession->getId(),  TutorialGrid::$validMove);
        $response = $this->jsonResponse();

        $this->assertSame(0, $response['errorsCount']);
        $this->assertSame('victory', $response['result']);
        $this->assertArrayHasKey('name', $response);
        $this->assertSame($puzzle->getName(), $response['name']);
        $this->assertArrayHasKey('objectGrid', $response);
        $this->assertSame('#feee9f', $response['objectGrid'][1][1][1]); // Not ideal, to change
    }

    public function testSameMoveOkNoChanges(): void
    {
        $gameSession = $this->createGame();

        $this->request($gameSession->getId(), TutorialGrid::$validMove);
        $this->request($gameSession->getId(), TutorialGrid::$validMove);
        $response = $this->jsonResponse();

        $this->assertSame(0, $response['errorsCount']);
        $this->assertSame('ok', $response['result']);
    }

    public function testSameErrorMoveNoChanges(): void
    {
        $gameSession = $this->createGame();

        $this->request($gameSession->getId(), TutorialGrid::$errorMove);
        $this->request($gameSession->getId(), TutorialGrid::$errorMove);
        $response = $this->jsonResponse();

        $this->assertSame(1, $response['errorsCount']); // ErrorCount only 1 not 2
        $this->assertSame('error', $response['result']);
    }

    public function testGoodMoveIsSaved(): void
    {
        $game = $this->createGame();

        $this->playMove($game, ['x' => 0, 'y' => 0, 'z' => 0]);

        $saved = $this->reload($game);
        $this->assertSame(CubeState::DESTROYED, $saved->getPlayerGrid()[0][0][0]);
        $this->assertSame(0, $saved->getErrorsCount());
    }

    public function testWrongMoveLocksTheCubeAndCountsAnError(): void
    {
        $game = $this->createGame();

        $this->playMove($game, ['x' => 1, 'y' => 1, 'z' => 1]);

        $this->assertSame('error', $this->jsonResponse()['result']);
        $saved = $this->reload($game);
        $this->assertSame(CubeState::LOCKED, $saved->getPlayerGrid()[1][1][1]);
        $this->assertSame(1, $saved->getErrorsCount());
    }

    public function testThirdErrorEndsTheGame(): void
    {
        $game = $this->createGame(errorsCount: 2);

        $this->playMove($game, ['x' => 1, 'y' => 1, 'z' => 1]);

        $this->assertSame('game-over', $this->jsonResponse()['result']);
        $saved = $this->reload($game);
        $this->assertSame(GameStatus::GAMEOVER, $saved->getStatus());
        $this->assertNotNull($saved->getFinishedAt());
    }

    private function playMove(GameSession $game, array $move, ?User $as = null): void
    {
        $server = $as ? ['HTTP_AUTHORIZATION' => 'Bearer ' . $this->createToken($as)] : [];

        $this->client->jsonRequest('POST', '/api/games/' . $game->getId() . '/moves', $move, $server);
    }

    private function reload(GameSession $gameSession): GameSession
    {
        $this->em->clear(); // forces a real read from the database

        return $this->em->find(GameSession::class, $gameSession->getId());
    }

    private function createGame(
        ?User $owner = null,
        GameStatus $status = GameStatus::PLAYING,
        int $errorsCount = 0,
        ?array $playerGrid = null,
        string $category = 'tutorial',
    ): GameSession {
        $puzzle = $this->createPuzzle($category, TutorialGrid::load(), $this->em);

        return $this->createGameSession(
            $puzzle,
            $this->em,
            playerGrid: $playerGrid ?? (new PlayerGridInitiate())->compute($puzzle->getObjectGrid()),
            user: $owner,
            status: $status,
            errorsCount: $errorsCount,
        );
    }
}
