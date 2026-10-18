<?php

namespace Tests\App\Entity;

use App\Entity\Initiative;
use App\Entity\Voting;
use App\Enum\InitiativeEnum;
use App\Enum\VotingEnum;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

class InitiativeStatusTest extends TestCase
{
    public function testIsFinished(): void
    {
        $initiative = new Initiative();
        $this->assertFalse($initiative->isFinished());

        $initiative->setState(InitiativeEnum::STATE_ACTIVE);
        $this->assertFalse($initiative->isFinished());

        $initiative->setState(InitiativeEnum::STATE_FINISHED);
        $this->assertTrue($initiative->isFinished());
    }

    public function testIsAcceptedForProgramType(): void
    {
        $initiative = new Initiative();
        $initiative->setType(InitiativeEnum::TYPE_PROGRAM);
        $initiative->setState(InitiativeEnum::STATE_FINISHED);

        $this->assertTrue($initiative->isAccepted());
        $this->assertFalse($initiative->isRejected());
    }

    public function testIsRejectedForPastType(): void
    {
        $initiative = new Initiative();
        $initiative->setType(InitiativeEnum::TYPE_PAST);
        $initiative->setState(InitiativeEnum::STATE_FINISHED);

        $this->assertTrue($initiative->isRejected());
        $this->assertFalse($initiative->isAccepted());
    }

    public function testStatusFromCurrentVoting(): void
    {
        $initiative = new Initiative();
        $initiative->setState(InitiativeEnum::STATE_FINISHED);

        $currentVoting = new Voting();
        $currentVoting->setType(VotingEnum::TYPE_CURRENT);
        $currentVoting->setState(VotingEnum::STATE_FINISHED);
        $currentVoting->setAccepted(true);
        $currentVoting->setRejected(false);

        $initiative->setVotings(new ArrayCollection([$currentVoting]));

        $this->assertTrue($initiative->isAccepted());
        $this->assertFalse($initiative->isRejected());
        $this->assertTrue($currentVoting->isFinished());
        $this->assertTrue($currentVoting->isAccepted());
        $this->assertFalse($currentVoting->isRejected());

        $currentVoting->setAccepted(false);
        $currentVoting->setRejected(true);

        $this->assertFalse($initiative->isAccepted());
        $this->assertTrue($initiative->isRejected());
    }

    public function testStatusFromFutureVotingOnly(): void
    {
        $initiative = new Initiative();
        $initiative->setState(InitiativeEnum::STATE_FINISHED);

        $futureVoting = new Voting();
        $futureVoting->setType(VotingEnum::TYPE_FUTURE);
        $futureVoting->setState(VotingEnum::STATE_FINISHED);
        $futureVoting->setAccepted(false);
        $futureVoting->setRejected(true);

        $initiative->setVotings(new ArrayCollection([$futureVoting]));

        $this->assertTrue($initiative->isRejected());
        $this->assertFalse($initiative->isAccepted());
    }

    public function testDecisionDateFallbackHierarchy(): void
    {
        $initiative = new Initiative();
        $createdAt = new DateTime('2026-01-01 10:00:00');
        $updatedAt = new DateTime('2026-02-01 10:00:00');
        $initiative->setCreatedAt($createdAt);

        // 1. Fallback to createdAt when updatedAt is null and no votings
        $this->assertSame($createdAt, $initiative->getDecisionDate());

        // 2. Fallback to updatedAt when set
        $initiative->setUpdatedAt($updatedAt);
        $this->assertSame($updatedAt, $initiative->getDecisionDate());

        // 3. Fallback to futureVoting enddate if currentVoting has no enddate
        $futureEnd = new DateTime('2026-03-01 12:00:00');
        $futureVoting = new Voting();
        $futureVoting->setType(VotingEnum::TYPE_FUTURE);
        $futureVoting->setEnddate($futureEnd);
        $initiative->setVotings(new ArrayCollection([$futureVoting]));

        $this->assertSame($futureEnd, $initiative->getDecisionDate());

        // 4. Prioritize currentVoting enddate when present
        $currentEnd = new DateTime('2026-04-01 18:00:00');
        $currentVoting = new Voting();
        $currentVoting->setType(VotingEnum::TYPE_CURRENT);
        $currentVoting->setEnddate($currentEnd);
        $initiative->setVotings(new ArrayCollection([$futureVoting, $currentVoting]));

        $this->assertSame($currentEnd, $initiative->getDecisionDate());
    }
}
