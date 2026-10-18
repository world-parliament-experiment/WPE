<?php

namespace Tests\App\Command;

use App\Command\AiAgentActionCommand;
use App\Entity\Category;
use App\Entity\Comment;
use App\Entity\Initiative;
use App\Entity\User;
use App\Entity\Voting;
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
}
