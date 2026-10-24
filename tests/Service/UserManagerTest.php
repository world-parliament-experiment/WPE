<?php

namespace Tests\App\Service;

use App\Entity\User;
use App\Service\UserManager;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class UserManagerTest extends TestCase
{
    public function testFindInactiveUsersWithoutActivitySetsCorrectThresholds(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $query = $this->createMock(AbstractQuery::class);

        $now = new \DateTime('2026-10-24 12:00:00');
        $expected12MonthsAgo = new \DateTime('2025-10-24 12:00:00');
        $expected1WeekAgo = new \DateTime('2026-10-17 12:00:00');

        $query->expects($this->exactly(2))
            ->method('setParameter')
            ->willReturnCallback(function (string $param, $value) use ($query, $expected12MonthsAgo, $expected1WeekAgo) {
                if ($param === 'twelveMonthsAgo') {
                    $this->assertEquals($expected12MonthsAgo, $value);
                } elseif ($param === 'oneWeekAgo') {
                    $this->assertEquals($expected1WeekAgo, $value);
                }
                return $query;
            });

        $user = new User();
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn([$user]);

        $em->expects($this->once())
            ->method('createQuery')
            ->with($this->callback(function (string $dql) {
                $this->assertStringContainsString('u.enabled = false', $dql);
                $this->assertStringContainsString('u.lastLogin IS NULL', $dql);
                $this->assertStringContainsString('u.isAi = false', $dql);
                $this->assertStringContainsString('u.expireAt <= :twelveMonthsAgo', $dql);
                $this->assertStringContainsString('u.registeredAt <= :oneWeekAgo', $dql);
                return true;
            }))
            ->willReturn($query);

        $manager = new UserManager($em);
        $result = $manager->findInactiveUsersWithoutActivity($now);

        $this->assertCount(1, $result);
        $this->assertSame($user, $result[0]);
    }
}
