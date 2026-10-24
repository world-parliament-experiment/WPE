<?php

namespace App\Command;

use App\Service\UserManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class DeleteInactiveUsersCommand extends Command
{
    protected static $defaultName = 'wpe:users:delete-inactive';

    private UserManager $userManager;

    public function __construct(UserManager $userManager)
    {
        parent::__construct();
        $this->userManager = $userManager;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Deletes unenabled users who have never logged in, are not AI agents, and have zero activity/foreign key references.')
            ->setHelp('This command safely removes inactive accounts that satisfy all of the following conditions:' . PHP_EOL .
                      '  1. enabled = false (in fos_user)' . PHP_EOL .
                      '  2. lastLogin is NULL (never logged in)' . PHP_EOL .
                      '  3. isAi is false (AI agents are never deleted)' . PHP_EOL .
                      '  4. Zero foreign key references or activity (comments, initiatives, votes, voters, delegations, favourites, user images)' . PHP_EOL .
                      '  5. Age checks:' . PHP_EOL .
                      '     - If an OTP was requested (expireAt is set): expireAt must be at least 12 months in the past.' . PHP_EOL .
                      '     - If an OTP was never requested: registeredAt must be older than 1 week.' . PHP_EOL .
                      'Can be scheduled as a server-level cron job.')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'List eligible users without deleting them.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $isDryRun = (bool) $input->getOption('dry-run');

        $inactiveUsers = $this->userManager->findInactiveUsersWithoutActivity();
        $count = count($inactiveUsers);

        if ($count === 0) {
            $io->success('No inactive users without activity found.');
            return Command::SUCCESS;
        }

        if ($isDryRun) {
            $io->note(sprintf('Dry-run mode: Found %d inactive user(s) eligible for deletion.', $count));
            $rows = [];
            foreach ($inactiveUsers as $user) {
                $registeredAt = $user->getRegisteredAt() ?? 'N/A';
                $expireAt = $user->getExpireAt() ? $user->getExpireAt()->format('Y-m-d H:i') : 'None (No OTP requested)';
                $rows[] = [$user->getId(), $user->getUsername(), $user->getEmail(), $registeredAt, $expireAt];
            }
            $io->table(['ID', 'Username', 'Email', 'Registered At', 'OTP Expire At'], $rows);
            return Command::SUCCESS;
        }

        $io->section(sprintf('Deleting %d inactive user(s)...', $count));

        $deletedCount = $this->userManager->deleteInactiveUsers();

        $io->success(sprintf('Successfully deleted %d inactive user(s).', $deletedCount));

        return Command::SUCCESS;
    }
}
