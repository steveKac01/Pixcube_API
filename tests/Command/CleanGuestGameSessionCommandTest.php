<?php

namespace App\Tests\Command;

use App\Entity\Category;
use App\Entity\GameSession;
use App\Entity\Puzzle;
use App\Entity\User;
use App\Tests\Support\CreateUser;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

use Symfony\Component\Console\Tester\CommandTester;


class CleanGuestGameSessionCommandTest extends KernelTestCase
{
    use CreateUser;
    private const SESSION_OUTDATED = "-16 days";
    private const SESSION_RECENT = "-13 days";
    private EntityManagerInterface $em;
    private Puzzle $puzzle;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        $this->puzzle = $this->createPuzzle();
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
                new DateTimeImmutable(self::SESSION_OUTDATED)
            );
        }

        $this->runCommand();

        $this->assertSame(0, $this->countGameSessions());
    }

    public function testDoesNotCleanRecentGameSession(): void
    {
        for ($i = 0; $i < 2; $i++) {
            $this->createGameSession($this->puzzle, new DateTimeImmutable(self::SESSION_RECENT));
        }

        $this->runCommand();

        $this->assertSame(2, $this->countGameSessions());
    }

    public function testDoesNotCleanUserGameSession(): void
    {
        $user = $this->createUser('user.test@test.fr');

        $this->createGameSession($this->puzzle, new DateTimeImmutable(self::SESSION_OUTDATED), $user);
        $this->createGameSession($this->puzzle, new DateTimeImmutable(self::SESSION_RECENT), $user);

        $this->assertSame(2, $this->countGameSessions());
    }

    private function createGameSession(Puzzle $puzzle, ?DateTimeImmutable $date = null, ?User $user = null): GameSession
    {
        $session = (new GameSession())->setErrorsCount(0)
            ->setPlayerGrid([])
            ->setPuzzle($puzzle)
            ->setErrorsCount(0);

        if ($date) {
            $reflection = new \ReflectionProperty(GameSession::class, 'updatedAt');

            $reflection->setValue(
                $session,
                $date
            );
        }
        $this->em->persist($session);
        $this->em->flush();

        return $session;
    }

    private function countGameSessions(): int
    {
        return $this->em->getRepository(GameSession::class)->count([]);
    }

    private function createPuzzle(): Puzzle
    {
        $category = (new Category())->setName('Tutorial')
            ->setSlug('tutorial')
            ->setDescription('Description test.');

        $puzzle = (new Puzzle())->setDifficulty(1)
            ->setIsPublished(true)
            ->setName('une radio')
            ->setObjectGrid([])
            ->setCategory($category);

        $this->em->persist($category);
        $this->em->persist($puzzle);
        $this->em->flush();

        return $puzzle;
    }
}
