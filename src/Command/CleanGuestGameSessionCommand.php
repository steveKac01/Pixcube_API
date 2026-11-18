<?php

namespace App\Command;

use App\Entity\GameSession;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:clean-game-session',
    description: 'Delete all old guest\'s game session.',
)]
class CleanGuestGameSessionCommand
{
    private const TIME_LIMIT = '-2 weeks';

    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {}

    public function __invoke(
        SymfonyStyle $io,
    ): int {

        $dateLimit = new \DateTimeImmutable($this::TIME_LIMIT);

        $count =  $this->em->createQueryBuilder()
            ->delete(GameSession::class, 'gs')
            ->where('gs.user IS NULL')
            ->andWhere('gs.updatedAt < :dateLimit')
            ->setParameter('dateLimit', $dateLimit)
            ->getQuery()
            ->execute();

        if ($count > 0) {
            $io->success(\sprintf('%s game session has been deleted.', $count));
            return Command::SUCCESS;
        }

        $io->info('No game session deleted.');
        return Command::SUCCESS;
    }
}
