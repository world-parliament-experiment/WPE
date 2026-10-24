<?php

namespace Tests\App\Command;

use App\Command\DeleteInactiveUsersCommand;
use App\Entity\User;
use App\Service\UserManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class DeleteInactiveUsersCommandTest extends TestCase
{
    public function testExecuteWhenNoInactiveUsers(): void
    {
        $userManager = $this->createMock(UserManager::class);
        $userManager->expects($this->once())
            ->method('findInactiveUsersWithoutActivity')
            ->willReturn([]);

        $userManager->expects($this->never())
            ->method('deleteInactiveUsers');

        $command = new DeleteInactiveUsersCommand($userManager);
        $tester = new CommandTester($command);

        $exitCode = $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('No inactive users without activity found.', $tester->getDisplay());
    }

    public function testExecuteDryRun(): void
    {
        $user = new User();
        $user->setUsername('inactive_user');
        $user->setEmail('inactive@example.com');
        $user->setRegisteredAt(new \DateTime('2025-01-01 10:00:00'));
        $user->setExpireAt(new \DateTime('2025-01-01 10:15:00'));

        $userManager = $this->createMock(UserManager::class);
        $userManager->expects($this->once())
            ->method('findInactiveUsersWithoutActivity')
            ->willReturn([$user]);

        $userManager->expects($this->never())
            ->method('deleteInactiveUsers');

        $command = new DeleteInactiveUsersCommand($userManager);
        $tester = new CommandTester($command);

        $exitCode = $tester->execute(['--dry-run' => true]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $display = $tester->getDisplay();
        $this->assertStringContainsString('Dry-run mode', $display);
        $this->assertStringContainsString('inactive_user', $display);
        $this->assertStringContainsString('2025-01-01', $display);
    }

    public function testExecuteDelete(): void
    {
        $user = new User();
        $user->setUsername('inactive_user');

        $userManager = $this->createMock(UserManager::class);
        $userManager->expects($this->once())
            ->method('findInactiveUsersWithoutActivity')
            ->willReturn([$user]);

        $userManager->expects($this->once())
            ->method('deleteInactiveUsers')
            ->willReturn(1);

        $command = new DeleteInactiveUsersCommand($userManager);
        $tester = new CommandTester($command);

        $exitCode = $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Successfully deleted 1 inactive user(s).', $tester->getDisplay());
    }
}
