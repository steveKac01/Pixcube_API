<?php

namespace App\Tests\Controller;

use App\Entity\GameSession;
use App\Enum\GameStatus;
use App\Tests\Support\CreateUser;
use App\Tests\Support\CreatePuzzle;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class CreateGameControllerTest extends WebTestCase
{
    use CreateUser, createPuzzle;
    private KernelBrowser $client;
    private EntityManagerInterface $em;
    private array $testGridPuzzle = [[[1]]];

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
    }

    public function testTutorialPuzzleWithoutLogin(): void
    {
        $this->createPuzzle('tutorial', $this->testGridPuzzle, $this->em);

        $this->client->request('POST', 'api/games');
        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertResponseStatusCodeSame(201);
        $this->assertArrayHasKey('gameId', $response);
        $this->assertArrayHasKey('puzzleId', $response);
        $this->assertArrayHasKey('status', $response);
        $this->assertArrayHasKey('errorsCount', $response);
        $this->assertArrayHasKey('clues', $response);
        $this->assertArrayHasKey('playerGrid', $response);
    }

    public function testUserNotLoggedWithNoTutorialPuzzleInDatabase(): void
    {
        $this->client->request('POST', 'api/games');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testUserNotLoggedWithTutorialPuzzleNotPublished(): void
    {
        $this->createPuzzle('tutorial', $this->testGridPuzzle, $this->em, false);

        $this->client->request('POST', 'api/games');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testUserNotLoggedWithPuzzleRequiresLogin(): void
    {
        $puzzle = $this->createPuzzle('test puzzle', $this->testGridPuzzle, $this->em);

        $this->client->request('POST', 'api/games/' . $puzzle->getId());
        $this->assertResponseStatusCodeSame(401);
    }

    public function testRouteWithInvalidIdPuzzle(): void
    {
        $this->client->request('POST', 'api/games/invalidId');
        $this->assertResponseStatusCodeSame(404);
    }

    public function testLoggedUserStartsAGameThatBelongsToThem(): void
    {
        $user = $this->createUser();
        $token = $this->createToken($user);

        $puzzle = $this->createPuzzle('puzzle de test', $this->testGridPuzzle, $this->em);

        $this->client->request('POST', 'api/games/' . $puzzle->getId(), server: ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]);

        $this->assertResponseStatusCodeSame(201);

        $response = json_decode($this->client->getResponse()->getContent(), true);

        $game = $this->em->find(GameSession::class, $response['gameId']);

        $this->assertSame($user->getEmail(), $game->getUser()?->getEmail());
    }

    public function testUserLoggedTriesUnknownPuzzle(): void
    {
        $user = $this->createUser();
        $token = $this->createToken($user);

        $this->client->request('POST', 'api/games/1', server: ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]);

        $this->assertResponseStatusCodeSame(404);
    }

    public function testUserLoggedWithPuzzleNotPublished(): void
    {
        $user = $this->createUser();
        $token = $this->createToken($user);
        $puzzle = $this->createPuzzle('puzzle de test', $this->testGridPuzzle, $this->em, false);

        $this->client->request('POST', 'api/games/' . $puzzle->getId(), server: ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]);

        $this->assertResponseStatusCodeSame(404);
    }

    public function testTutorialPuzzleDoesntHaveOwner(): void
    {
        $this->createPuzzle('tutorial', $this->testGridPuzzle, $this->em);

        $this->client->request('POST', 'api/games');
        $response = json_decode($this->client->getResponse()->getContent(), true);

        $game = $this->em->find(GameSession::class, $response['gameId']);

        $this->assertSame(null, $game->getUser());
    }

    public function testUserLoggedStartsSamePuzzleTwoTimes(): void
    {
        $user = $this->createUser();
        $token = $this->createToken($user);

        $puzzle = $this->createPuzzle('puzzle test', $this->testGridPuzzle, $this->em);

        // first request
        $this->client->request('POST', 'api/games/' . $puzzle->getId(), server: ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]);
        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertResponseStatusCodeSame(201);

        // second request (same as the first one)
        $this->client->request('POST', $puzzle->getId(), server: ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]);
        $gameId = $response['gameId'];

        $this->assertResponseStatusCodeSame(200);

        $secondResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame($gameId, $secondResponse['gameId']);

        $this->assertSame(1, count($this->em->getRepository(GameSession::class)->findBy(['user' => $user, 'puzzle' => $puzzle])));
    }

    public function testGuestStartsSameTutorialPuzzleTwoTimes(): void
    {
        $this->createPuzzle('tutorial', $this->testGridPuzzle, $this->em);

        $this->client->request('POST', 'api/games');
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $firstGameId = $response['gameId'];

        $this->client->request('POST', '');
        $secondResponse = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertNotSame($firstGameId, $secondResponse['gameId']);
    }

    public function testGuestCreateAPuzzleAlreadyFinished(): void
    {
        $user = $this->createUser();
        $token = $this->createToken($user);

        $puzzle = $this->createPuzzle('Test puzzle', $this->testGridPuzzle, $this->em);

        $this->client->request('POST', 'api/games/' . $puzzle->getId(), server: ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]);

        $response = json_decode($this->client->getResponse()->getContent(), true);

        // Set the victory for this game session
        $gameSession = $this->em->find(GameSession::class, $response['gameId']);
        $gameSession->setStatus(GameStatus::VICTORY);
        $this->em->flush();

        $this->client->request('POST', $puzzle->getId(), server: ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]);

        $this->assertResponseStatusCodeSame(201);
    }
}
