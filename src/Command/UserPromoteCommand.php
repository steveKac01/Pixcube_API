<?php

namespace App\Command;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:user:promote',
    description: 'Grant ROLE_ADMIN to an existing user',
)]
class UserPromoteCommand
{
    public function __construct(
        private readonly UserRepository $user_repository,
        private readonly EntityManagerInterface $em,
    ) {}

    public function __invoke(
        SymfonyStyle $io,

        #[Argument('Email of the user to promote')] string $email,
    ): int {
        $user = $this->user_repository->findOneBy(['email' => $email]);

        if (!$user) {
            $io->error(\sprintf('User with email "%s" doesn\'t exist.',$email));
            return Command::FAILURE;
        }

        if (in_array($user::ROLE_ADMIN, $user->getRoles(), true)) {
            $io->note('The user is already an admin');
            return Command::SUCCESS;
        }

        $user->setRoles([$user::ROLE_ADMIN]);
        $this->em->flush();

        $io->success(sprintf('The user "%s" is now admin.',$email));

        return Command::SUCCESS;
    }
}
