<?php

namespace Tests\App\Command;

use App\Command\AiAgentActionCommand;
use App\Entity\Category;
use App\Entity\Comment;
use App\Entity\Initiative;
use App\Entity\User;
use App\Entity\Voting;
use App\Entity\Vote;
use App\Enum\VotingEnum;
use App\Enum\InitiativeEnum;
use App\Service\AiAssistantService;
use App\Service\VoteEncryptionService;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class AiAgentActionCommandTest extends TestCase
{
    private function createMockQueryBuilder(array $arrayResult = []): QueryBuilder
    {
        $query = $this->createMock(AbstractQuery::class);
        $query->method('getArrayResult')->willReturn($arrayResult);
        $query->method('getResult')->willReturn($arrayResult);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('select')->willReturnSelf();
        $qb->method('from')->willReturnSelf();
        $qb->method('join')->willReturnSelf();
        $qb->method('where')->willReturnSelf();
        $qb->method('andWhere')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $qb->method('orderBy')->willReturnSelf();
        $qb->method('setMaxResults')->willReturnSelf();
        $qb->method('getQuery')->willReturn($query);

        return $qb;
    }

    public function testExecuteCounterProposalAction()
    {
        // 1. Mock AI Agent User
        $aiAgent = $this->createMock(User::class);
        $aiAgent->method('getUsername')->willReturn('ai_agent_john');
        $aiAgent->method('getAiPersona')->willReturn('progressive');

        // 2. Mock Category
        $category = $this->createMock(Category::class);
        $category->method('getId')->willReturn(1);
        $category->method('getName')->willReturn('Global Governance');
        $category->method('getType')->willReturn(0);

        // 3. Mock Target Initiative
        $targetInitiative = $this->createMock(Initiative::class);
        $targetInitiative->method('getId')->willReturn(10);
        $targetInitiative->method('getTitle')->willReturn('Original Climate Action Bill');
        $targetInitiative->method('getDescription')->willReturn('Proposed global climate measures.');
        $targetInitiative->method('getCategory')->willReturn($category);
        $targetInitiative->method('getSlug')->willReturn('original-climate-action-bill');

        // 4. Mock User Repository
        $userRepo = $this->createMock(EntityRepository::class);
        $userRepo->expects($this->once())
            ->method('findBy')
            ->with(['isAi' => true])
            ->willReturn([$aiAgent]);

        // 5. Mock Initiative Repository
        $initiativeRepo = $this->createMock(EntityRepository::class);
        $initiativeRepo->method('find')
            ->with('10')
            ->willReturn($targetInitiative);

        // 6. Mock EntityManager
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')
            ->willReturnCallback(function ($entityClass) use ($userRepo, $initiativeRepo) {
                if ($entityClass === User::class) {
                    return $userRepo;
                }
                if ($entityClass === Initiative::class) {
                    return $initiativeRepo;
                }
                return $this->createMock(EntityRepository::class);
            });

        $qb = $this->createMockQueryBuilder([]);
        $em->method('createQueryBuilder')->willReturn($qb);

        // 7. Mock AiAssistantService
        $aiAssistant = $this->createMock(AiAssistantService::class);
        $aiAssistant->expects($this->once())
            ->method('generateComment')
            ->with('Original Climate Action Bill', 'Proposed global climate measures.', 'progressive')
            ->willReturn('We need stricter enforcement on carbon emissions.');

        $aiAssistant->expects($this->once())
            ->method('draftCounterProposal')
            ->with('Original Climate Action Bill', 'Proposed global climate measures.', 'We need stricter enforcement on carbon emissions.', 'progressive')
            ->willReturn([
                'title' => 'Enhanced Climate Action & Carbon Enforcement Bill',
                'description' => '### Section 1\nStricter penalties for high carbon emitters.'
            ]);

        // 8. Mock VoteEncryptionService
        $voteEncryption = $this->createMock(VoteEncryptionService::class);

        // 9. Instantiate command and CommandTester
        $command = new AiAgentActionCommand($aiAssistant, $em, $voteEncryption);
        $application = new Application();
        $application->add($command);

        $commandTester = new CommandTester($application->find('app:ai:run-agents'));
        $commandTester->execute([
            'action' => 'counter-proposal',
            '--initiative' => '10'
        ]);

        $output = preg_replace('/\s+/', ' ', $commandTester->getDisplay());

        $this->assertEquals(0, $commandTester->getStatusCode());
        $this->assertStringContainsString('Agent selected: ai_agent_john', $output);
        $this->assertStringContainsString('New counter-proposal created', $output);
        $this->assertStringContainsString('Enhanced Climate Action', $output);
    }

    public function testExecuteDraftActionSuccessWithNoDuplicate()
    {
        $aiAgent = $this->createMock(User::class);
        $aiAgent->method('getUsername')->willReturn('ai_agent_greta');
        $aiAgent->method('getAiPersona')->willReturn('environmentalist');

        $category = $this->createMock(Category::class);
        $category->method('getId')->willReturn(2);
        $category->method('getName')->willReturn('Environment');
        $category->method('getType')->willReturn(0);

        $userRepo = $this->createMock(EntityRepository::class);
        $userRepo->method('findBy')->with(['isAi' => true])->willReturn([$aiAgent]);

        $categoryRepo = $this->createMock(EntityRepository::class);
        $categoryRepo->method('findOneBy')->with(['name' => 'Environment'])->willReturn($category);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturnCallback(function ($entityClass) use ($userRepo, $categoryRepo) {
            if ($entityClass === User::class) return $userRepo;
            if ($entityClass === Category::class) return $categoryRepo;
            return $this->createMock(EntityRepository::class);
        });

        $existingProposals = [
            ['id' => 1, 'title' => 'Solar Subsidies Mandate', 'description' => 'Subsidize residential solar.']
        ];
        $qb = $this->createMockQueryBuilder($existingProposals);
        $em->method('createQueryBuilder')->willReturn($qb);

        $aiAssistant = $this->createMock(AiAssistantService::class);
        $aiAssistant->expects($this->once())
            ->method('inventTopic')
            ->with('environmentalist', 'Environment', ['Solar Subsidies Mandate'])
            ->willReturn('Ocean Microplastic Filtration Incentive');

        $aiAssistant->expects($this->once())
            ->method('draftFullInitiative')
            ->with('Ocean Microplastic Filtration Incentive', 'environmentalist')
            ->willReturn([
                'title' => 'Global Ocean Microplastic Filtration Initiative',
                'description' => 'Install microplastic filters in port facilities.'
            ]);

        $aiAssistant->expects($this->once())
            ->method('checkSemanticDuplicate')
            ->with(
                'Global Ocean Microplastic Filtration Initiative',
                $this->anything(),
                $existingProposals,
                'environmentalist',
                'Environment'
            )
            ->willReturn([
                'is_duplicate' => false,
                'duplicate_of_id' => null,
                'reason' => 'Addresses plastic filtration, not solar subsidies.'
            ]);

        $voteEncryption = $this->createMock(VoteEncryptionService::class);

        $command = new AiAgentActionCommand($aiAssistant, $em, $voteEncryption);
        $application = new Application();
        $application->add($command);

        $commandTester = new CommandTester($application->find('app:ai:run-agents'));
        $commandTester->execute([
            'action' => 'draft',
            '--category' => 'Environment'
        ]);

        $output = preg_replace('/\s+/', ' ', $commandTester->getDisplay());

        $this->assertEquals(0, $commandTester->getStatusCode());
        $this->assertStringContainsString('New initiative created', $output);
        $this->assertStringContainsString('Global Ocean Microplastic Filtration Initiative', $output);
    }

    public function testExecuteDraftActionRetryOnSemanticDuplicate()
    {
        $aiAgent = $this->createMock(User::class);
        $aiAgent->method('getUsername')->willReturn('ai_agent_greta');
        $aiAgent->method('getAiPersona')->willReturn('environmentalist');

        $category = $this->createMock(Category::class);
        $category->method('getId')->willReturn(2);
        $category->method('getName')->willReturn('Environment');
        $category->method('getType')->willReturn(0);

        $userRepo = $this->createMock(EntityRepository::class);
        $userRepo->method('findBy')->with(['isAi' => true])->willReturn([$aiAgent]);

        $categoryRepo = $this->createMock(EntityRepository::class);
        $categoryRepo->method('findOneBy')->with(['name' => 'Environment'])->willReturn($category);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturnCallback(function ($entityClass) use ($userRepo, $categoryRepo) {
            if ($entityClass === User::class) return $userRepo;
            if ($entityClass === Category::class) return $categoryRepo;
            return $this->createMock(EntityRepository::class);
        });

        $existingProposals = [
            ['id' => 99, 'title' => 'Global Carbon Pricing Standard', 'description' => 'A tax on carbon emissions.']
        ];
        $qb = $this->createMockQueryBuilder($existingProposals);
        $em->method('createQueryBuilder')->willReturn($qb);

        $aiAssistant = $this->createMock(AiAssistantService::class);
        // Attempt 1 invents a duplicate topic
        $aiAssistant->expects($this->exactly(2))
            ->method('inventTopic')
            ->willReturnOnConsecutiveCalls(
                'International Fossil Fuel Carbon Tax',
                'International Reforestation and Wetland Restoration Treaty'
            );

        $aiAssistant->expects($this->exactly(2))
            ->method('draftFullInitiative')
            ->willReturnOnConsecutiveCalls(
                [
                    'title' => 'International Fossil Fuel Carbon Tax Bill',
                    'description' => 'Impose fees on carbon emissions.'
                ],
                [
                    'title' => 'Wetland Restoration Treaty',
                    'description' => 'Restore global wetlands.'
                ]
            );

        // Attempt 1 duplicate check flags duplicate, Attempt 2 passes
        $aiAssistant->expects($this->exactly(2))
            ->method('checkSemanticDuplicate')
            ->willReturnOnConsecutiveCalls(
                [
                    'is_duplicate' => true,
                    'duplicate_of_id' => 99,
                    'reason' => 'Both proposals establish a carbon tax.'
                ],
                [
                    'is_duplicate' => false,
                    'duplicate_of_id' => null,
                    'reason' => 'Unique topic.'
                ]
            );

        $voteEncryption = $this->createMock(VoteEncryptionService::class);

        $command = new AiAgentActionCommand($aiAssistant, $em, $voteEncryption);
        $application = new Application();
        $application->add($command);

        $commandTester = new CommandTester($application->find('app:ai:run-agents'));
        $commandTester->execute([
            'action' => 'draft',
            '--category' => 'Environment'
        ]);

        $output = preg_replace('/\s+/', ' ', $commandTester->getDisplay());

        $this->assertEquals(0, $commandTester->getStatusCode());
        $this->assertStringContainsString('semantic duplicate of proposal #99', $output);
        $this->assertStringContainsString('Retrying with a new topic', $output);
        $this->assertStringContainsString('Wetland Restoration Treaty', $output);
    }

    public function testExecuteDraftActionAbortsWhenTopicIsDuplicateAndNoForce()
    {
        $aiAgent = $this->createMock(User::class);
        $aiAgent->method('getUsername')->willReturn('ai_agent_bob');
        $aiAgent->method('getAiPersona')->willReturn('conservative');

        $category = $this->createMock(Category::class);
        $category->method('getId')->willReturn(3);
        $category->method('getName')->willReturn('Economy');
        $category->method('getType')->willReturn(0);

        $userRepo = $this->createMock(EntityRepository::class);
        $userRepo->method('findBy')->with(['isAi' => true])->willReturn([$aiAgent]);

        $categoryRepo = $this->createMock(EntityRepository::class);
        $categoryRepo->method('findOneBy')->with(['name' => 'Economy'])->willReturn($category);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturnCallback(function ($entityClass) use ($userRepo, $categoryRepo) {
            if ($entityClass === User::class) return $userRepo;
            if ($entityClass === Category::class) return $categoryRepo;
            return $this->createMock(EntityRepository::class);
        });

        $existingProposals = [
            ['id' => 77, 'title' => 'Global Minimum Wealth Tax', 'description' => 'Tax ultra wealthy individuals.']
        ];
        $qb = $this->createMockQueryBuilder($existingProposals);
        $em->method('createQueryBuilder')->willReturn($qb);

        $aiAssistant = $this->createMock(AiAssistantService::class);
        $aiAssistant->expects($this->once())
            ->method('draftFullInitiative')
            ->with('Billionaire Tax Framework', 'conservative')
            ->willReturn([
                'title' => 'International Billionaire Tax Framework',
                'description' => 'Annual tax on billionaires.'
            ]);

        $aiAssistant->expects($this->once())
            ->method('checkSemanticDuplicate')
            ->willReturn([
                'is_duplicate' => true,
                'duplicate_of_id' => 77,
                'reason' => 'Both tax high-net-worth individuals.'
            ]);

        $voteEncryption = $this->createMock(VoteEncryptionService::class);

        $command = new AiAgentActionCommand($aiAssistant, $em, $voteEncryption);
        $application = new Application();
        $application->add($command);

        $commandTester = new CommandTester($application->find('app:ai:run-agents'));
        $commandTester->execute([
            'action' => 'draft',
            '--category' => 'Economy',
            '--topic' => 'Billionaire Tax Framework'
        ]);

        $output = preg_replace('/\s+/', ' ', $commandTester->getDisplay());

        $this->assertEquals(1, $commandTester->getStatusCode());
        $this->assertStringContainsString('Proposal not submitted', $output);
        $this->assertStringContainsString('semantic duplicate of existing proposal #77', $output);
    }

    public function testExecuteDraftActionSucceedsWhenTopicIsDuplicateWithForce()
    {
        $aiAgent = $this->createMock(User::class);
        $aiAgent->method('getUsername')->willReturn('ai_agent_bob');
        $aiAgent->method('getAiPersona')->willReturn('conservative');

        $category = $this->createMock(Category::class);
        $category->method('getId')->willReturn(3);
        $category->method('getName')->willReturn('Economy');
        $category->method('getType')->willReturn(0);

        $userRepo = $this->createMock(EntityRepository::class);
        $userRepo->method('findBy')->with(['isAi' => true])->willReturn([$aiAgent]);

        $categoryRepo = $this->createMock(EntityRepository::class);
        $categoryRepo->method('findOneBy')->with(['name' => 'Economy'])->willReturn($category);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturnCallback(function ($entityClass) use ($userRepo, $categoryRepo) {
            if ($entityClass === User::class) return $userRepo;
            if ($entityClass === Category::class) return $categoryRepo;
            return $this->createMock(EntityRepository::class);
        });

        $existingProposals = [
            ['id' => 77, 'title' => 'Global Minimum Wealth Tax', 'description' => 'Tax ultra wealthy individuals.']
        ];
        $qb = $this->createMockQueryBuilder($existingProposals);
        $em->method('createQueryBuilder')->willReturn($qb);

        $aiAssistant = $this->createMock(AiAssistantService::class);
        $aiAssistant->expects($this->once())
            ->method('draftFullInitiative')
            ->with('Billionaire Tax Framework', 'conservative')
            ->willReturn([
                'title' => 'International Billionaire Tax Framework',
                'description' => 'Annual tax on billionaires.'
            ]);

        // Duplicate check should be skipped when --force is passed
        $aiAssistant->expects($this->never())->method('checkSemanticDuplicate');

        $voteEncryption = $this->createMock(VoteEncryptionService::class);

        $command = new AiAgentActionCommand($aiAssistant, $em, $voteEncryption);
        $application = new Application();
        $application->add($command);

        $commandTester = new CommandTester($application->find('app:ai:run-agents'));
        $commandTester->execute([
            'action' => 'draft',
            '--category' => 'Economy',
            '--topic' => 'Billionaire Tax Framework',
            '--force' => true
        ]);

        $output = preg_replace('/\s+/', ' ', $commandTester->getDisplay());

        $this->assertEquals(0, $commandTester->getStatusCode());
        $this->assertStringContainsString('New initiative created', $output);
    }

    public function testExecuteCounterProposalAbortsOnSemanticDuplicate()
    {
        $aiAgent = $this->createMock(User::class);
        $aiAgent->method('getUsername')->willReturn('ai_agent_john');
        $aiAgent->method('getAiPersona')->willReturn('progressive');

        $category = $this->createMock(Category::class);
        $category->method('getId')->willReturn(1);
        $category->method('getName')->willReturn('Global Governance');
        $category->method('getType')->willReturn(0);

        $targetInitiative = $this->createMock(Initiative::class);
        $targetInitiative->method('getId')->willReturn(10);
        $targetInitiative->method('getTitle')->willReturn('Original Proposal');
        $targetInitiative->method('getDescription')->willReturn('Some proposal.');
        $targetInitiative->method('getCategory')->willReturn($category);

        $userRepo = $this->createMock(EntityRepository::class);
        $userRepo->method('findBy')->with(['isAi' => true])->willReturn([$aiAgent]);

        $initiativeRepo = $this->createMock(EntityRepository::class);
        $initiativeRepo->method('find')->with('10')->willReturn($targetInitiative);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturnCallback(function ($entityClass) use ($userRepo, $initiativeRepo) {
            if ($entityClass === User::class) return $userRepo;
            if ($entityClass === Initiative::class) return $initiativeRepo;
            return $this->createMock(EntityRepository::class);
        });

        $existingProposals = [
            ['id' => 55, 'title' => 'Prior Counter Proposal', 'description' => 'Already counter proposed.']
        ];
        $qb = $this->createMockQueryBuilder($existingProposals);
        $em->method('createQueryBuilder')->willReturn($qb);

        $aiAssistant = $this->createMock(AiAssistantService::class);
        $aiAssistant->expects($this->once())
            ->method('generateComment')
            ->willReturn('Critique text.');

        $aiAssistant->expects($this->once())
            ->method('draftCounterProposal')
            ->willReturn([
                'title' => 'Duplicate Counter Proposal',
                'description' => 'Same content as proposal 55.'
            ]);

        $aiAssistant->expects($this->once())
            ->method('checkSemanticDuplicate')
            ->willReturn([
                'is_duplicate' => true,
                'duplicate_of_id' => 55,
                'reason' => 'Replicates prior counter proposal #55.'
            ]);

        $voteEncryption = $this->createMock(VoteEncryptionService::class);

        $command = new AiAgentActionCommand($aiAssistant, $em, $voteEncryption);
        $application = new Application();
        $application->add($command);

        $commandTester = new CommandTester($application->find('app:ai:run-agents'));
        $commandTester->execute([
            'action' => 'counter-proposal',
            '--initiative' => '10'
        ]);

        $output = preg_replace('/\s+/', ' ', $commandTester->getDisplay());

        $this->assertEquals(1, $commandTester->getStatusCode());
        $this->assertStringContainsString('Counter-proposal not submitted', $output);
        $this->assertStringContainsString('semantic duplicate of existing proposal #55', $output);
    }

    private function createInitiativeMock(int $id, string $title, ?User $createdBy, ?Voting $futureVoting, ?Voting $currentVoting, int $type = InitiativeEnum::TYPE_FUTURE, ?\DateTime $createdAt = null): Initiative
    {
        $initiative = $this->createMock(Initiative::class);
        $initiative->method('getId')->willReturn($id);
        $initiative->method('getTitle')->willReturn($title);
        $initiative->method('getDescription')->willReturn('Description for ' . $title);
        $initiative->method('getCreatedBy')->willReturn($createdBy);
        $initiative->method('getFutureVoting')->willReturn($futureVoting ?: false);
        $initiative->method('getCurrentVoting')->willReturn($currentVoting ?: false);
        $initiative->method('getType')->willReturn($type);
        $initiative->method('getCreatedAt')->willReturn($createdAt ?? new \DateTime());
        return $initiative;
    }

    private function createVotingMock(int $id, int $state, int $type): Voting
    {
        $voting = $this->createMock(Voting::class);
        $voting->method('getId')->willReturn($id);
        $voting->method('getState')->willReturn($state);
        $voting->method('getType')->willReturn($type);
        $voting->method('getStartdate')->willReturn(new \DateTime('-1 hour'));
        return $voting;
    }

    public function testExecuteVoteActionPrioritizesOwnProposalsAndOwnOngoingVotes()
    {
        $aiAgent = $this->createMock(User::class);
        $aiAgent->method('getId')->willReturn(1);
        $aiAgent->method('getUsername')->willReturn('ai_agent_maya');
        $aiAgent->method('getAiPersona')->willReturn('progressive');

        $otherUser = $this->createMock(User::class);
        $otherUser->method('getId')->willReturn(2);
        $otherUser->method('getUsername')->willReturn('other_human');

        // Own proposal and own ongoing vote
        $ownPropVoting = $this->createVotingMock(101, VotingEnum::STATE_OPEN, VotingEnum::TYPE_FUTURE);
        $ownProposal = $this->createInitiativeMock(1, 'Own Renewable Energy Bill', $aiAgent, $ownPropVoting, null, InitiativeEnum::TYPE_FUTURE, new \DateTime('-2 hours'));

        $ownCurrentVoting = $this->createVotingMock(102, VotingEnum::STATE_OPEN, VotingEnum::TYPE_CURRENT);
        $ownOngoingVote = $this->createInitiativeMock(2, 'Own Universal Healthcare Law', $aiAgent, null, $ownCurrentVoting, InitiativeEnum::TYPE_CURRENT, new \DateTime('-3 hours'));

        // Other proposal and other ongoing vote
        $otherPropVoting = $this->createVotingMock(103, VotingEnum::STATE_OPEN, VotingEnum::TYPE_FUTURE);
        $otherProposal = $this->createInitiativeMock(3, 'Other Ocean Cleanup Initiative', $otherUser, $otherPropVoting, null, InitiativeEnum::TYPE_FUTURE);

        $otherCurrentVoting = $this->createVotingMock(104, VotingEnum::STATE_OPEN, VotingEnum::TYPE_CURRENT);
        $otherOngoingVote = $this->createInitiativeMock(4, 'Other Space Governance Treaty', $otherUser, null, $otherCurrentVoting, InitiativeEnum::TYPE_CURRENT);

        $userRepo = $this->createMock(EntityRepository::class);
        $userRepo->method('findBy')->with(['isAi' => true])->willReturn([$aiAgent]);

        $voteRepo = $this->createMock(EntityRepository::class);
        // None of them are voted on yet
        $voteRepo->method('findOneBy')->willReturn(null);

        $qb = $this->createMockQueryBuilder([$otherProposal, $ownProposal, $otherOngoingVote, $ownOngoingVote]);

        $initiativeRepo = $this->createMock(EntityRepository::class);
        $initiativeRepo->method('createQueryBuilder')->willReturn($qb);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturnCallback(function ($entityClass) use ($userRepo, $initiativeRepo, $voteRepo) {
            if ($entityClass === User::class) return $userRepo;
            if ($entityClass === Initiative::class) return $initiativeRepo;
            if ($entityClass === Vote::class) return $voteRepo;
            return $this->createMock(EntityRepository::class);
        });
        $em->method('createQueryBuilder')->willReturn($qb);

        // For own proposals and own ongoing votes, decideVote is NOT called (author always votes 1)
        $aiAssistant = $this->createMock(AiAssistantService::class);
        $aiAssistant->expects($this->never())->method('decideVote');

        $voteEncryption = $this->createMock(VoteEncryptionService::class);
        $voteEncryption->method('encrypt')->willReturnCallback(fn($val) => 'enc_' . $val);

        // Exactly 2 votes should be cast: 1 on own proposal, 1 on own ongoing vote
        $persistedVotes = [];
        $em->expects($this->exactly(2))
            ->method('persist')
            ->with($this->callback(function ($entity) use (&$persistedVotes) {
                if ($entity instanceof Vote) {
                    $persistedVotes[] = $entity;
                }
                return true;
            }));
        $em->expects($this->exactly(2))->method('flush');

        $command = new AiAgentActionCommand($aiAssistant, $em, $voteEncryption);
        $application = new Application();
        $application->add($command);

        $commandTester = new CommandTester($application->find('app:ai:run-agents'));
        $commandTester->execute(['action' => 'vote']);

        $output = preg_replace('/\s+/', ' ', $commandTester->getDisplay());

        $this->assertEquals(0, $commandTester->getStatusCode());
        $this->assertStringContainsString('Voting on own proposal: Own Renewable Energy Bill', $output);
        $this->assertStringContainsString('Voted: 1 (Supported own proposal)', $output);
        $this->assertStringContainsString('Voting on own ongoing vote: Own Universal Healthcare Law', $output);
        $this->assertStringContainsString('Voted: 1 (Voted YES on own ongoing vote)', $output);

        $this->assertCount(2, $persistedVotes);
        $this->assertSame($ownPropVoting, $persistedVotes[0]->getVoting());
        $this->assertSame($ownCurrentVoting, $persistedVotes[1]->getVoting());
    }

    public function testExecuteVoteActionFallsBackToOtherProposalsAndOngoingVotesWhenNoOwnUnvoted()
    {
        $aiAgent = $this->createMock(User::class);
        $aiAgent->method('getId')->willReturn(1);
        $aiAgent->method('getUsername')->willReturn('ai_agent_maya');
        $aiAgent->method('getAiPersona')->willReturn('progressive');

        $otherUser = $this->createMock(User::class);
        $otherUser->method('getId')->willReturn(2);
        $otherUser->method('getUsername')->willReturn('other_human');

        $otherPropVoting = $this->createVotingMock(103, VotingEnum::STATE_OPEN, VotingEnum::TYPE_FUTURE);
        $otherProposal = $this->createInitiativeMock(3, 'Other Ocean Cleanup Initiative', $otherUser, $otherPropVoting, null, InitiativeEnum::TYPE_FUTURE);

        $otherCurrentVoting = $this->createVotingMock(104, VotingEnum::STATE_OPEN, VotingEnum::TYPE_CURRENT);
        $otherOngoingVote = $this->createInitiativeMock(4, 'Other Space Governance Treaty', $otherUser, null, $otherCurrentVoting, InitiativeEnum::TYPE_CURRENT);

        $userRepo = $this->createMock(EntityRepository::class);
        $userRepo->method('findBy')->with(['isAi' => true])->willReturn([$aiAgent]);

        $voteRepo = $this->createMock(EntityRepository::class);
        $voteRepo->method('findOneBy')->willReturn(null);

        $qb = $this->createMockQueryBuilder([$otherProposal, $otherOngoingVote]);

        $initiativeRepo = $this->createMock(EntityRepository::class);
        $initiativeRepo->method('createQueryBuilder')->willReturn($qb);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturnCallback(function ($entityClass) use ($userRepo, $initiativeRepo, $voteRepo) {
            if ($entityClass === User::class) return $userRepo;
            if ($entityClass === Initiative::class) return $initiativeRepo;
            if ($entityClass === Vote::class) return $voteRepo;
            return $this->createMock(EntityRepository::class);
        });
        $em->method('createQueryBuilder')->willReturn($qb);

        $aiAssistant = $this->createMock(AiAssistantService::class);
        // decideVote should be called for proposal and for ongoing vote
        $aiAssistant->expects($this->exactly(2))
            ->method('decideVote')
            ->willReturnMap([
                ['Other Ocean Cleanup Initiative', 'Description for Other Ocean Cleanup Initiative', 'progressive', false, 1],
                ['Other Space Governance Treaty', 'Description for Other Space Governance Treaty', 'progressive', true, -1],
            ]);

        $voteEncryption = $this->createMock(VoteEncryptionService::class);
        $voteEncryption->method('encrypt')->willReturnCallback(fn($val) => 'enc_' . $val);

        $persistedVotes = [];
        $em->expects($this->exactly(2))
            ->method('persist')
            ->with($this->callback(function ($entity) use (&$persistedVotes) {
                if ($entity instanceof Vote) {
                    $persistedVotes[] = $entity;
                }
                return true;
            }));
        $em->expects($this->exactly(2))->method('flush');

        $command = new AiAgentActionCommand($aiAssistant, $em, $voteEncryption);
        $application = new Application();
        $application->add($command);

        $commandTester = new CommandTester($application->find('app:ai:run-agents'));
        $commandTester->execute(['action' => 'vote']);

        $output = preg_replace('/\s+/', ' ', $commandTester->getDisplay());

        $this->assertEquals(0, $commandTester->getStatusCode());
        $this->assertStringContainsString('Evaluating proposal: Other Ocean Cleanup Initiative', $output);
        $this->assertStringContainsString('Voted: 1 (Supported proposal)', $output);
        $this->assertStringContainsString('Voting on official ongoing vote: Other Space Governance Treaty', $output);
        $this->assertStringContainsString('Voted: -1', $output);

        $this->assertCount(2, $persistedVotes);
        $this->assertSame($otherPropVoting, $persistedVotes[0]->getVoting());
        $this->assertSame($otherCurrentVoting, $persistedVotes[1]->getVoting());
    }

    public function testExecuteVoteActionVotesOnlyOnProposalWhenNoOngoingVotesAvailable()
    {
        $aiAgent = $this->createMock(User::class);
        $aiAgent->method('getId')->willReturn(1);
        $aiAgent->method('getUsername')->willReturn('ai_agent_maya');
        $aiAgent->method('getAiPersona')->willReturn('progressive');

        $otherUser = $this->createMock(User::class);
        $otherUser->method('getId')->willReturn(2);

        $propVoting = $this->createVotingMock(103, VotingEnum::STATE_OPEN, VotingEnum::TYPE_FUTURE);
        $proposal = $this->createInitiativeMock(3, 'Affordable Housing Initiative', $otherUser, $propVoting, null, InitiativeEnum::TYPE_FUTURE);

        $userRepo = $this->createMock(EntityRepository::class);
        $userRepo->method('findBy')->with(['isAi' => true])->willReturn([$aiAgent]);

        $voteRepo = $this->createMock(EntityRepository::class);
        $voteRepo->method('findOneBy')->willReturn(null);

        $qb = $this->createMockQueryBuilder([$proposal]);

        $initiativeRepo = $this->createMock(EntityRepository::class);
        $initiativeRepo->method('createQueryBuilder')->willReturn($qb);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturnCallback(function ($entityClass) use ($userRepo, $initiativeRepo, $voteRepo) {
            if ($entityClass === User::class) return $userRepo;
            if ($entityClass === Initiative::class) return $initiativeRepo;
            if ($entityClass === Vote::class) return $voteRepo;
            return $this->createMock(EntityRepository::class);
        });
        $em->method('createQueryBuilder')->willReturn($qb);

        $aiAssistant = $this->createMock(AiAssistantService::class);
        $aiAssistant->expects($this->once())
            ->method('decideVote')
            ->with('Affordable Housing Initiative', 'Description for Affordable Housing Initiative', 'progressive', false)
            ->willReturn(1);

        $voteEncryption = $this->createMock(VoteEncryptionService::class);
        $voteEncryption->method('encrypt')->willReturnCallback(fn($val) => 'enc_' . $val);

        $em->expects($this->once())->method('persist')->with($this->isInstanceOf(Vote::class));
        $em->expects($this->once())->method('flush');

        $command = new AiAgentActionCommand($aiAssistant, $em, $voteEncryption);
        $application = new Application();
        $application->add($command);

        $commandTester = new CommandTester($application->find('app:ai:run-agents'));
        $commandTester->execute(['action' => 'vote']);

        $output = preg_replace('/\s+/', ' ', $commandTester->getDisplay());

        $this->assertEquals(0, $commandTester->getStatusCode());
        $this->assertStringContainsString('Voted: 1 (Supported proposal)', $output);
        $this->assertStringContainsString('No ongoing votes available for this agent to vote on.', $output);
    }

    public function testExecuteVoteActionVotesOnlyOnOngoingVoteWhenNoProposalsAvailable()
    {
        $aiAgent = $this->createMock(User::class);
        $aiAgent->method('getId')->willReturn(1);
        $aiAgent->method('getUsername')->willReturn('ai_agent_maya');
        $aiAgent->method('getAiPersona')->willReturn('progressive');

        $otherUser = $this->createMock(User::class);
        $otherUser->method('getId')->willReturn(2);

        $currentVoting = $this->createVotingMock(104, VotingEnum::STATE_OPEN, VotingEnum::TYPE_CURRENT);
        $ongoingVote = $this->createInitiativeMock(4, 'Global Internet Freedom Act', $otherUser, null, $currentVoting, InitiativeEnum::TYPE_CURRENT);

        $userRepo = $this->createMock(EntityRepository::class);
        $userRepo->method('findBy')->with(['isAi' => true])->willReturn([$aiAgent]);

        $voteRepo = $this->createMock(EntityRepository::class);
        $voteRepo->method('findOneBy')->willReturn(null);

        $qb = $this->createMockQueryBuilder([$ongoingVote]);

        $initiativeRepo = $this->createMock(EntityRepository::class);
        $initiativeRepo->method('createQueryBuilder')->willReturn($qb);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturnCallback(function ($entityClass) use ($userRepo, $initiativeRepo, $voteRepo) {
            if ($entityClass === User::class) return $userRepo;
            if ($entityClass === Initiative::class) return $initiativeRepo;
            if ($entityClass === Vote::class) return $voteRepo;
            return $this->createMock(EntityRepository::class);
        });
        $em->method('createQueryBuilder')->willReturn($qb);

        $aiAssistant = $this->createMock(AiAssistantService::class);
        $aiAssistant->expects($this->once())
            ->method('decideVote')
            ->with('Global Internet Freedom Act', 'Description for Global Internet Freedom Act', 'progressive', true)
            ->willReturn(1);

        $voteEncryption = $this->createMock(VoteEncryptionService::class);
        $voteEncryption->method('encrypt')->willReturnCallback(fn($val) => 'enc_' . $val);

        $em->expects($this->once())->method('persist')->with($this->isInstanceOf(Vote::class));
        $em->expects($this->once())->method('flush');

        $command = new AiAgentActionCommand($aiAssistant, $em, $voteEncryption);
        $application = new Application();
        $application->add($command);

        $commandTester = new CommandTester($application->find('app:ai:run-agents'));
        $commandTester->execute(['action' => 'vote']);

        $output = preg_replace('/\s+/', ' ', $commandTester->getDisplay());

        $this->assertEquals(0, $commandTester->getStatusCode());
        $this->assertStringContainsString('No proposals available for this agent to vote on.', $output);
        $this->assertStringContainsString('Voting on official ongoing vote: Global Internet Freedom Act', $output);
        $this->assertStringContainsString('Voted: 1', $output);
    }

    public function testExecuteVoteActionSkipsUnsupportedProposals()
    {
        $aiAgent = $this->createMock(User::class);
        $aiAgent->method('getId')->willReturn(1);
        $aiAgent->method('getUsername')->willReturn('ai_agent_maya');
        $aiAgent->method('getAiPersona')->willReturn('progressive');

        $otherUser = $this->createMock(User::class);
        $otherUser->method('getId')->willReturn(2);

        $voting1 = $this->createVotingMock(201, VotingEnum::STATE_OPEN, VotingEnum::TYPE_FUTURE);
        $prop1 = $this->createInitiativeMock(11, 'Unappealing Proposal', $otherUser, $voting1, null, InitiativeEnum::TYPE_FUTURE);

        $userRepo = $this->createMock(EntityRepository::class);
        $userRepo->method('findBy')->with(['isAi' => true])->willReturn([$aiAgent]);

        $voteRepo = $this->createMock(EntityRepository::class);
        $voteRepo->method('findOneBy')->willReturn(null);

        $qb = $this->createMockQueryBuilder([$prop1]);

        $initiativeRepo = $this->createMock(EntityRepository::class);
        $initiativeRepo->method('createQueryBuilder')->willReturn($qb);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturnCallback(function ($entityClass) use ($userRepo, $initiativeRepo, $voteRepo) {
            if ($entityClass === User::class) return $userRepo;
            if ($entityClass === Initiative::class) return $initiativeRepo;
            if ($entityClass === Vote::class) return $voteRepo;
            return $this->createMock(EntityRepository::class);
        });
        $em->method('createQueryBuilder')->willReturn($qb);

        $aiAssistant = $this->createMock(AiAssistantService::class);
        $aiAssistant->expects($this->once())
            ->method('decideVote')
            ->with('Unappealing Proposal', 'Description for Unappealing Proposal', 'progressive', false)
            ->willReturn(0); // Not supported

        $voteEncryption = $this->createMock(VoteEncryptionService::class);

        // No votes persisted since proposal was not supported and no ongoing votes exist
        $em->expects($this->never())->method('persist');
        $em->expects($this->never())->method('flush');

        $command = new AiAgentActionCommand($aiAssistant, $em, $voteEncryption);
        $application = new Application();
        $application->add($command);

        $commandTester = new CommandTester($application->find('app:ai:run-agents'));
        $commandTester->execute(['action' => 'vote']);

        $output = preg_replace('/\s+/', ' ', $commandTester->getDisplay());

        $this->assertEquals(0, $commandTester->getStatusCode());
        $this->assertStringContainsString('Decided not to support.', $output);
        $this->assertStringContainsString('No candidate proposals were supported in this round.', $output);
        $this->assertStringContainsString('No votes cast by this agent in this round.', $output);
    }

    public function testExecuteVoteActionOwnNewProposalsInStateWaitingAutoActivate()
    {
        $aiAgent = $this->createMock(User::class);
        $aiAgent->method('getId')->willReturn(1);
        $aiAgent->method('getUsername')->willReturn('ai_agent_maya');
        $aiAgent->method('getAiPersona')->willReturn('progressive');

        $waitingVoting = $this->createVotingMock(301, VotingEnum::STATE_WAITING, VotingEnum::TYPE_FUTURE);
        // Expect setState(VotingEnum::STATE_OPEN) to be called
        $waitingVoting->expects($this->once())->method('setState')->with(VotingEnum::STATE_OPEN);

        $ownNewProposal = $this->createInitiativeMock(21, 'Brand New Agent Draft', $aiAgent, $waitingVoting, null, InitiativeEnum::TYPE_FUTURE);

        $userRepo = $this->createMock(EntityRepository::class);
        $userRepo->method('findBy')->with(['isAi' => true])->willReturn([$aiAgent]);

        $voteRepo = $this->createMock(EntityRepository::class);
        $voteRepo->method('findOneBy')->willReturn(null);

        $qb = $this->createMockQueryBuilder([$ownNewProposal]);

        $initiativeRepo = $this->createMock(EntityRepository::class);
        $initiativeRepo->method('createQueryBuilder')->willReturn($qb);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturnCallback(function ($entityClass) use ($userRepo, $initiativeRepo, $voteRepo) {
            if ($entityClass === User::class) return $userRepo;
            if ($entityClass === Initiative::class) return $initiativeRepo;
            if ($entityClass === Vote::class) return $voteRepo;
            return $this->createMock(EntityRepository::class);
        });
        $em->method('createQueryBuilder')->willReturn($qb);

        $aiAssistant = $this->createMock(AiAssistantService::class);
        $aiAssistant->expects($this->never())->method('decideVote');

        $voteEncryption = $this->createMock(VoteEncryptionService::class);
        $voteEncryption->method('encrypt')->willReturnCallback(fn($val) => 'enc_' . $val);

        // Persist should be called for the activated voting, and for the vote
        $em->expects($this->exactly(2))->method('persist');
        $em->expects($this->once())->method('flush');

        $command = new AiAgentActionCommand($aiAssistant, $em, $voteEncryption);
        $application = new Application();
        $application->add($command);

        $commandTester = new CommandTester($application->find('app:ai:run-agents'));
        $commandTester->execute(['action' => 'vote']);

        $output = preg_replace('/\s+/', ' ', $commandTester->getDisplay());

        $this->assertEquals(0, $commandTester->getStatusCode());
        $this->assertStringContainsString('Voting on own proposal: Brand New Agent Draft', $output);
        $this->assertStringContainsString('Voted: 1 (Supported own proposal)', $output);
    }
}

