<?php

namespace App\Tests\Entity;

use App\Entity\Category;
use App\Entity\GameSession;
use App\Entity\Puzzle;
use App\Enum\GameStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Uuid;

class GameSessionPersistenceTest extends KernelTestCase
{
    public function testGameSessionIsPersistedWithItsRelation(): void
    {
        $kernel = self::bootKernel();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $category = (new Category())
            ->setName('Tutorial')
            ->setDescription('How to play')
            ->setSlug('tutorial');

        $puzzle = (new Puzzle)
            ->setName('cubes')
            ->setCategory($category)
            ->setDifficulty(1)
            ->setSizeX(2)
            ->setSizeY(2)
            ->setSizeZ(2)
            ->setObjectGrid([
                [['#ff0000', null], [null, null]],
                [[null, null], [null, '#00ff00']],
            ]);

        $gameSession = (new GameSession)
            ->setPuzzle($puzzle)
            ->setPlayerGrid([
                [[0, 0], [0, 0]],
                [[0, 0], [0, 0]],
            ]);

        $em->persist($category);
        $em->persist($puzzle);
        $em->persist($gameSession);
        $em->flush();

        $id = $gameSession->getId();
        $em->clear();

        $found = $em->find(GameSession::class, $id);

        $this->assertInstanceOf(Uuid::class, $id);
        $this->assertSame(0, $found->getErrorsCount());
        $this->assertSame(GameStatus::PLAYING, $found->getStatus());
        $this->assertNull($found->getUser());
        $this->assertSame('tutorial', $found->getPuzzle()->getCategory()->getSlug());
        $this->assertSame('#ff0000', $found->getPuzzle()->getObjectGrid()[0][0][0]);
    }
}
