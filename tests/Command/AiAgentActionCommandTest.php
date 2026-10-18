<?php

namespace Tests\App\Command;

use App\Command\AiAgentActionCommand;
use App\Entity\Category;
use App\Entity\Comment;
use App\Entity\Initiative;
use App\Entity\User;
use App\Service\AiAssistantService;
use App\Service\VoteEncryptionService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class AiAgentActionCommandTest extends TestCase
{
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

        $output = $commandTester->getDisplay();

        $this->assertEquals(0, $commandTester->getStatusCode());
        $this->assertStringContainsString('Agent selected: ai_agent_john', $output);
        $this->assertStringContainsString('New counter-proposal created', $output);
        $this->assertStringContainsString('Enhanced Climate Action & Carbon Enforcement Bill', $output);
    }
}
