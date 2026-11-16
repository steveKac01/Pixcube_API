<?php

namespace App\Tests\Controller;

use App\Entity\Category;
use App\Entity\Puzzle;
use App\Entity\User;
use App\Tests\Support\TutorialGrid;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class CreateGameControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testTutorialPuzzleWithoutLogin()
    {
        $this->createPuzzle('tutorial');

        $this->client->request('POST', 'api/games');
        $response = \json_decode($this->client->getResponse()->getContent(), true);

        $this->assertResponseStatusCodeSame(201);
        $this->assertArrayHasKey('gameId', $response);
        $this->assertArrayHasKey('puzzleId', $response);
        $this->assertArrayHasKey('status', $response);
        $this->assertArrayHasKey('errorsCount', $response);
        $this->assertArrayHasKey('clues', $response);
        $this->assertArrayHasKey('playerGrid', $response);
    }

    public function testWithoutTutorialPuzzleInDatabase()
    {
        $this->client->request('POST', 'api/games');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testWithTutorialPuzzleNotPublished()
    {
        $this->createPuzzle('tutorial', false);

        $this->client->request('POST', 'api/games');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testPuzzleWithIdWithoutLogin()
    {
        $puzzle = $this->createPuzzle('test puzzle');

        $this->client->request('POST', 'api/games/' . $puzzle->getId());
        $this->assertResponseStatusCodeSame(401);
    }

    public function testRouteWithInvalidId()
    {
        $this->client->request('POST', 'api/games/invalidId');
        $this->assertResponseStatusCodeSame(404);
    }

    //Un autre puzzle avec token

    //Puzzle inconnu avec token

    //Puzzle non publié avec token

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

    private function createUser(): User
    {
        $user = (new User())->setEmail('user@test.fr')
            ->setPassword('Password123!');

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->persist($user);
        $em->flush();

        return $user;
    }
}
