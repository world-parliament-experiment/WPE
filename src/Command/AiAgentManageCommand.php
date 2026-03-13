<?php

namespace App\Command;

use App\Entity\User;
use App\Service\AiAssistantService;
use App\Util\UserManipulator;
use App\Service\UserManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Doctrine\ORM\EntityManagerInterface;

class AiAgentManageCommand extends Command
{
    protected static $defaultName = 'app:ai:manage-agents';
    
    private $aiAssistant;
    private $userManipulator;
    private $userManager;
    private $entityManager;

    public function __construct(
        AiAssistantService $aiAssistant,
        UserManipulator $userManipulator,
        UserManager $userManager,
        EntityManagerInterface $entityManager
    ) {
        parent::__construct();
        $this->aiAssistant = $aiAssistant;
        $this->userManipulator = $userManipulator;
        $this->userManager = $userManager;
        $this->entityManager = $entityManager;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Synchronizes AI personas from config to AI Agent users in the database')
            ->setHelp('This command reads config/ai_personas.json and ensures a User exists for each persona.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $personas = $this->aiAssistant->getPersonas();

        if (empty($personas)) {
            $io->error('No personas found in config/ai_personas.json');
            return Command::FAILURE;
        }

        foreach ($personas as $key => $data) {
            $user = $this->entityManager->getRepository(User::class)->findOneBy(['aiPersona' => $key]);

            // Create a clean username from the name (e.g., "Julian Vane" -> "JulianVane")
            $cleanUsername = str_replace(' ', '', $data['name']);
            $email = strtolower($cleanUsername) . '@world-parliament.org';

            if (!$user) {
                $io->info(sprintf('Creating new AI agent: %s (%s)', $cleanUsername, $key));
                // Use a random password as AI agents don't login via web
                $password = bin2hex(random_bytes(16));
                $user = $this->userManipulator->create(
                    $cleanUsername,
                    $data['name'],
                    $data['affiliation'],
                    $password,
                    $email,
                    true,
                    false
                );
            } else {
                $io->info(sprintf('Updating existing AI agent: %s', $key));
                $user->setUsername($cleanUsername);
                $user->setFirstname($data['name']);
                $user->setLastname($data['affiliation']);
                $user->setEmail($email);
                $user->setEmailCanonical(strtolower($email));
            }

            // Set AI specific fields
            $user->setIsAi(true);
            $user->setAiPersona($key);
            $user->setCountry('UN'); // Set to Global Level as requested
            $user->setDescription($data['description']);
            
            $this->userManager->updateUser($user);
        }

        $io->success('AI agents synchronized successfully.');

        return Command::SUCCESS;
    }
}
