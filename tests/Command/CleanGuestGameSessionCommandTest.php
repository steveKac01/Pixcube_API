<?php

namespace App\Tests\Command;

use App\Entity\GameSession;
use App\Entity\Puzzle;
use App\Tests\Support\CreateUser;
use App\Tests\Support\CreatePuzzle;
use App\Tests\Support\CreateGameSession;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class CleanGuestGameSessionCommandTest extends KernelTestCase
{
    use CreateUser, createPuzzle, createGameSession;

    private DateTimeImmutable $outdated;
    private DateTimeImmutable $recent;
    private EntityManagerInterface $em;
    private Puzzle $puzzle;

    protected function setUp(): void
    {
        self::bootKernel();
        $days = static::getContainer()->getParameter('app.guest_game_retention_days');
        $this->outdated = new DateTimeImmutable(sprintf('-%d days', $days + 1));
        $this->recent = new DateTimeImmutable(sprintf('-%d days', $days - 1));
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        $this->puzzle = $this->createPuzzle('tutorial', [[[1]]], $this->em);
    }

    private function runCommand(): void
    {
        $tester = new CommandTester((new Application(self::$kernel))->find('app:clean-game-session'));
        $tester->execute([]);
        $tester->assertCommandIsSuccessful();
    }

    public function testCleanTwoOutdatedGameSession(): void
    {
        for ($i = 0; $i < 2; $i++) {
            $this->createGameSession(
                $this->puzzle,
                $this->em,
                $this->outdated,
                []
            );
        }

        $this->runCommand();

        $this->assertSame(0, $this->countGameSessions());
    }

    public function testDoesNotCleanRecentGameSession(): void
    {
        for ($i = 0; $i < 2; $i++) {
            $this->createGameSession($this->puzzle, $this->em, $this->recent, []);
        }

        $this->runCommand();

        $this->assertSame(2, $this->countGameSessions());
    }

    public function testDoesNotCleanUserGameSession(): void
    {
        $user = $this->createUser('user.test@test.fr');

        $this->createGameSession($this->puzzle, $this->em, $this->outdated, [], $user);
        $this->createGameSession($this->puzzle, $this->em, $this->recent, [], $user);

        $this->assertSame(2, $this->countGameSessions());
    }

    private function countGameSessions(): int
    {
        return $this->em->getRepository(GameSession::class)->count([]);
    }
}
