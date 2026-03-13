<?php

namespace App\Command;

use App\Entity\Initiative;
use App\Entity\User;
use App\Service\AiAssistantService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Doctrine\ORM\EntityManagerInterface;

class AiAgentRepairCommand extends Command
{
    protected static $defaultName = 'app:ai:repair-initiatives';
    
    private $aiAssistant;
    private $entityManager;

    public function __construct(
        AiAssistantService $aiAssistant,
        EntityManagerInterface $entityManager
    ) {
        parent::__construct();
        $this->aiAssistant = $aiAssistant;
        $this->entityManager = $entityManager;
    }

    protected function configure(): void
    {
        $this->setDescription('Detects and repairs AI initiatives with broken JSON formatting.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        // Find initiatives created by AI where description starts with { or contains ```json
        $qb = $this->entityManager->createQueryBuilder();
        $initiatives = $qb->select('i')
            ->from(Initiative::class, 'i')
            ->join('i.createdBy', 'u')
            ->where('u.isAi = true')
            ->andWhere($qb->expr()->orX(
                $qb->expr()->like('i.description', $qb->expr()->literal('{%')),
                $qb->expr()->like('i.description', $qb->expr()->literal('```json%')),
                $qb->expr()->like('i.description', $qb->expr()->literal('{%'))
            ))
            ->getQuery()
            ->getResult();

        // The DQL LIKE might be tricky with characters, let's just fetch all AI ones and filter in PHP for safety
        $allAiInitiatives = $this->entityManager->getRepository(Initiative::class)
            ->createQueryBuilder('i')
            ->join('i.createdBy', 'u')
            ->where('u.isAi = true')
            ->getQuery()
            ->getResult();

        $toRepair = [];
        foreach ($allAiInitiatives as $i) {
            $desc = trim(strip_tags($i->getDescription()));
            if (str_starts_with($desc, '{') || str_starts_with($desc, '```json')) {
                $toRepair[] = $i;
            }
        }

        if (empty($toRepair)) {
            $io->success('No broken AI initiatives detected.');
            return Command::SUCCESS;
        }

        $io->note(sprintf('Found %d broken initiatives. Starting repair...', count($toRepair)));

        foreach ($toRepair as $initiative) {
            $io->section(sprintf('Repairing ID %d: %s', $initiative->getId(), $initiative->getTitle()));
            
            /** @var User $agent */
            $agent = $initiative->getCreatedBy();
            
            // We use the original title as the topic for re-generation, 
            // cleaning up the "Proposal: " prefix if it exists from previous fallback
            $topic = str_replace('Proposal: ', '', $initiative->getTitle());

            try {
                $draft = $this->aiAssistant->draftFullInitiative($topic, $agent->getAiPersona());
                
                $initiative->setTitle($draft['title']);
                $initiative->setDescription($draft['description']);
                
                $this->entityManager->flush();
                $io->success('Repaired.');
            } catch (\Exception $e) {
                $io->error('Failed to repair: ' . $e->getMessage());
            }
            
            // Sleep a bit to avoid rate limits during batch repair
            sleep(5);
        }

        $io->success('Repair process completed.');
        return Command::SUCCESS;
    }
}
