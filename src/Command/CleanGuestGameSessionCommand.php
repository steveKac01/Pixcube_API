<?php

namespace App\Command;

use App\Entity\GameSession;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'app:clean-game-session',
    description: 'Delete all old guests game sessions.',
)]
class CleanGuestGameSessionCommand
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        #[Autowire('%app.guest_game_retention_days%')] readonly int $retentionDays,
    ) {}

    public function __invoke(
        SymfonyStyle $io,
    ): int {

        $dateLimit = new \DateTimeImmutable(sprintf('-%d days', $this->retentionDays));

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
