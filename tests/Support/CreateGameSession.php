<?php

namespace App\Tests\Support;

use App\Entity\GameSession;
use App\Entity\Puzzle;
use App\Entity\User;
use App\Enum\GameStatus;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

trait CreateGameSession
{

    private function createGameSession(Puzzle $puzzle, ?EntityManagerInterface $em, ?DateTimeImmutable $date = null, ?array $playerGrid = [], ?User $user = null, ?int $errorsCount = 0, ?GameStatus $status = GameStatus::PLAYING): GameSession
    {
        $session = (new GameSession())->setErrorsCount($errorsCount)
            ->setPlayerGrid($playerGrid)
            ->setPuzzle($puzzle)
            ->setStatus($status)
            ->setUser($user);

        // No setter for updatedAt
        if ($date) {
            $reflection = new \ReflectionProperty(GameSession::class, 'updatedAt');

            $reflection->setValue(
                $session,
                $date
            );
        }
        $em->persist($session);
        $em->flush();

        return $session;
    }
}
