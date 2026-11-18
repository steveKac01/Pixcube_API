<?php

namespace App\Tests\Controller;

use App\Entity\Category;
use App\Entity\GameSession;
use App\Entity\Puzzle;
use App\Enum\GameStatus;
use App\Tests\Support\CreateUser;
use App\Tests\Support\TutorialGrid;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class CreateGameControllerTest extends WebTestCase
{
    use CreateUser;
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testTutorialPuzzleWithoutLogin(): void
    {
        $this->createPuzzle('tutorial');

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
        $this->createPuzzle('tutorial', false);

        $this->client->request('POST', 'api/games');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testUserNotLoggedWithPuzzleRequiresLogin(): void
    {
        $puzzle = $this->createPuzzle('test puzzle');

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

        $puzzle = $this->createPuzzle('puzzle de test');

        $this->client->request('POST', 'api/games/' . $puzzle->getId(), server: ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]);

        $this->assertResponseStatusCodeSame(201);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $game = $em->find(GameSession::class, $response['gameId']);

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
        $puzzle = $this->createPuzzle('puzzle de test', false);

        $this->client->request('POST', 'api/games/' . $puzzle->getId(), server: ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]);

        $this->assertResponseStatusCodeSame(404);
    }

    public function testTutorialPuzzleDoesntHaveOwner(): void
    {
        $this->createPuzzle('tutorial');

        $this->client->request('POST', 'api/games');
        $response = json_decode($this->client->getResponse()->getContent(), true);

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $game = $em->find(GameSession::class, $response['gameId']);

        $this->assertSame(null, $game->getUser());
    }

    public function testUserLoggedStartsSamePuzzleTwoTimes(): void
    {
        $user = $this->createUser();
        $token = $this->createToken($user);

        $puzzle = $this->createPuzzle('puzzle test');

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

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $this->assertSame(1, count($em->getRepository(GameSession::class)->findBy(['user' => $user, 'puzzle' => $puzzle])));
    }

    public function testGuestStartsSameTutorialPuzzleTwoTimes(): void
    {

        $this->createPuzzle('tutorial');

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

        $puzzle = $this->createPuzzle('puzzle de test');

        $this->client->request('POST', 'api/games/' . $puzzle->getId(), server: ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]);

        $response = json_decode($this->client->getResponse()->getContent(), true);

        // Set the victory for this game session
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $gameSession = $em->find(GameSession::class, $response['gameId']);
        $gameSession->setStatus(GameStatus::VICTORY);
        $em->flush();


        $this->client->request('POST', $puzzle->getId(), server: ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]);

        $this->assertResponseStatusCodeSame(201);
    }

    private function createPuzzle(string $slug, bool $isPublished = true): Puzzle
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $category = (new Category())->setName(ucfirst($slug))
            ->setSlug($slug)
            ->setDescription('This is a test category');

        $puzzle = (new Puzzle())->setName('Test object')
            ->setCategory($category)
            ->setDifficulty(1)
            ->setIsPublished($isPublished)
            ->setObjectGrid(TutorialGrid::load());

        $em->persist($category);
        $em->persist($puzzle);
        $em->flush();

        return $puzzle;
    }
}
