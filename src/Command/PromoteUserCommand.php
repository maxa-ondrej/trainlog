<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Role;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:user:promote',
    description: 'Promote a user to ROLE_ADMIN by email address.',
)]
final class PromoteUserCommand {
    public function __construct(
        private readonly UserRepository $users,
        private readonly EntityManagerInterface $em,
    ) {}

    public function __invoke(
        SymfonyStyle $io,
        #[Argument(description: 'Email of the user to promote')]
        string $email,
    ): int {
        $user = $this->users->findOneBy(['email' => $email]);
        if ($user === null) {
            $io->error(sprintf('No user with email "%s" exists.', $email));

            return Command::FAILURE;
        }

        if ($user->getRole() === Role::Admin) {
            $io->note(sprintf('User "%s" is already an admin.', $email));

            return Command::SUCCESS;
        }

        $user->setRole(Role::Admin);
        $this->em->flush();

        $io->success(sprintf('User "%s" has been promoted to admin.', $email));

        return Command::SUCCESS;
    }
}
