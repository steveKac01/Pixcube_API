<?php

namespace App\Tests\Controller;

use App\Enum\GameStatus;
use App\Game\CubeState;
use App\Tests\Support\CreateGameSession;
use App\Tests\Support\CreatePuzzle;
use App\Tests\Support\CreateUser;
use App\Tests\Support\TutorialGrid;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;


final class PlayerMoveControllerTest extends WebTestCase
{
    use CreateUser, CreatePuzzle, CreateGameSession;
    private KernelBrowser $client;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
    }

    private function guestRequest(string $gameSessionId, array $moveCoordinate): array
    {
        $this->client->jsonRequest('POST', '/api/games/' . $gameSessionId . '/moves', $moveCoordinate);

        return $this->getData();
    }

    private function loggedRequest(string $token, string $gameSessionId, array $moveCoordinate): array
    {
        $this->client->jsonRequest('POST', '/api/games/' . $gameSessionId . '/moves', $moveCoordinate,  server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token
        ]);

        return $this->getData();
    }

    private function getData(): array
    {
        return  json_decode($this->client->getResponse()->getContent(), true);
    }

    public function testControllerRejectGetMethod(): void
    {
        $tutorialPuzzle = $this->createPuzzle('tutorial', TutorialGrid::load(), $this->em);
        $playerGrid = $this->initiatePlayerGrid($tutorialPuzzle->getObjectGrid());
        $gameSession = $this->createGameSession($tutorialPuzzle, $this->em, null, $playerGrid);
        $this->client->jsonRequest('GET', '/api/games/' . $gameSession->getId() . '/moves', ['x' => 1, 'y' => 1, 'z' => 1]);

        $this->assertResponseStatusCodeSame('405');
    }

    #[DataProvider('wrongPlayerMoves')]
    public function testWrongPlayerMoveRequest(array $playerMove): void
    {
        $tutorialPuzzle = $this->createPuzzle('tutorial', TutorialGrid::load(), $this->em);
        $playerGrid = $this->initiatePlayerGrid($tutorialPuzzle->getObjectGrid());
        $gameSession = $this->createGameSession($tutorialPuzzle, $this->em, null, $playerGrid);

        $this->guestRequest($gameSession->getId(), $playerMove);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testUserGuestCanPlayTutorialGrid(): void
    {
        $tutorialPuzzle = $this->createPuzzle('tutorial', TutorialGrid::load(), $this->em);
        $playerGrid = $this->initiatePlayerGrid($tutorialPuzzle->getObjectGrid());
        $gameSession = $this->createGameSession($tutorialPuzzle, $this->em, null, $playerGrid);
        $this->guestRequest($gameSession->getId(), ['x' => 1, 'y' => 1, 'z' => 1]);

        $this->assertResponseStatusCodeSame(200);
    }

    public function testGuestCanNotPlayPuzzleOtherThanTutorial(): void
    {
        $user = $this->createUser();

        $puzzle = $this->createPuzzle('Puzzle test', TutorialGrid::load(), $this->em);
        $playerGrid = $this->initiatePlayerGrid($puzzle->getObjectGrid());

        $gameSession = $this->createGameSession($puzzle, $this->em, null, $playerGrid, $user);

        $this->guestRequest($gameSession->getId(), ['x' => 1, 'y' => 1, 'z' => 1]);

        $this->assertResponseStatusCodeSame(401);
    }

    public function testUserLoggedCanPlayHisGuestSession(): void
    {
        $tutorialPuzzle = $this->createPuzzle('tutorial', TutorialGrid::load(), $this->em);
        $playerGrid = $this->initiatePlayerGrid($tutorialPuzzle->getObjectGrid());
        $gameSession = $this->createGameSession($tutorialPuzzle, $this->em, null, $playerGrid);
        $user = $this->createUser();
        $token = $this->createToken($user);

        $this->loggedRequest($token, $gameSession->getId(), ['x' => 1, 'y' => 1, 'z' => 1]);
        $this->assertResponseStatusCodeSame(200);
    }

    public function testUserCanPlayHisPuzzleWhereLoginIsRequired(): void
    {
        $user = $this->createUser();
        $token = $this->createToken($user);
        $puzzle = $this->createPuzzle('Puzzle test', TutorialGrid::load(), $this->em);
        $playerGrid = $this->initiatePlayerGrid($puzzle->getObjectGrid());
        $gameSession = $this->createGameSession($puzzle, $this->em, null, $playerGrid, $user);

        $this->loggedRequest($token, $gameSession->getId(), ['x' => 1, 'y' => 1, 'z' => 1]);

        $this->assertResponseStatusCodeSame(200);
    }

    public function testUserCanNotPlayAnotherUserPuzzle(): void
    {
        $user = $this->createUser();
        $token = $this->createToken($user);
        $userOwningGameSession = $this->createUser('another@email.fr');
        $puzzle = $this->createPuzzle('Puzzle test', TutorialGrid::load(), $this->em);
        $playerGrid = $this->initiatePlayerGrid($puzzle->getObjectGrid());

        $gameSession = $this->createGameSession($puzzle, $this->em, null, $playerGrid, $userOwningGameSession);

        $this->loggedRequest($token, $gameSession->getId(), ['x' => 1, 'y' => 1, 'z' => 1]);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testCanPlayOnlyGameWhereStatusEqualPlaying(): void
    {
        $puzzle = $this->createPuzzle('Puzzle test', TutorialGrid::load(), $this->em);
        $playerGrid = $this->initiatePlayerGrid($puzzle->getObjectGrid());
        $gameSession = $this->createGameSession($puzzle, $this->em, null, $playerGrid, status: GameStatus::VICTORY);

        $this->guestRequest($gameSession->getId(), ['x' => 1, 'y' => 1, 'z' => 1]);
        $this->assertResponseStatusCodeSame(409);
    }

    public function testPlayerMoveOutOfBound(): void
    {
        $puzzle = $this->createPuzzle('Puzzle test', TutorialGrid::load(), $this->em);
        $playerGrid = $this->initiatePlayerGrid($puzzle->getObjectGrid());
        $gameSession = $this->createGameSession($puzzle, $this->em, null, $playerGrid);

        $this->guestRequest($gameSession->getId(), ['x' => 10, 'y' => 1, 'z' => 1]);
        $this->assertResponseStatusCodeSame(422);
    }

    public static function wrongPlayerMoves(): iterable
    {
        yield 'empty array' => [[]];
        yield 'negative coordinate X ' => [['x' => -1, 'y' => 1, 'z' => 1]];
        yield 'negative coordinate y ' => [['x' => 0, 'y' => -1, 'z' => 1]];
        yield 'negative coordinate z ' => [['x' => 0, 'y' => 1, 'z' => -1]];
    }

    private function initiatePlayerGrid(array $solutionGrid): array
    {

        $sizeX = count($solutionGrid);
        $sizeY = count($solutionGrid[0] ?? []);
        $sizeZ = count($solutionGrid[0][0] ?? []);

        $playerGrid = [];

        for ($x = 0; $x < $sizeX; $x++) {
            for ($y = 0; $y < $sizeY; $y++) {
                for ($z = 0; $z < $sizeZ; $z++) {
                    $playerGrid[$x][$y][$z] = CubeState::INTACT;
                }
            }
        }

        return $playerGrid;
    }
}
