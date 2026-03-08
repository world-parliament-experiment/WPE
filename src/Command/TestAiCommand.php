<?php

namespace App\Command;

use App\Service\AiAssistantService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class TestAiCommand extends Command
{
    protected static $defaultName = 'app:test-ai';
    private $aiAssistant;

    public function __construct(AiAssistantService $aiAssistant)
    {
        parent::__construct();
        $this->aiAssistant = $aiAssistant;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Test the AI Assistant with different personas')
            ->addArgument('prompt', InputArgument::REQUIRED, 'The proposal topic to draft')
            ->addOption('persona', 'p', InputOption::VALUE_REQUIRED, 'The persona to use (neutral, conservative, environmentalist, socialist, libertarian)', 'neutral')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $prompt = $input->getArgument('prompt');
        $persona = $input->getOption('persona');

        $io->title('AI Assistant Test');
        $io->info(sprintf('Using persona: %s', $persona));
        $io->info(sprintf('Prompt: %s', $prompt));

        try {
            $draft = $this->aiAssistant->draftProposal($prompt, $persona);
            $io->section('Generated Draft:');
            $io->writeln($draft);
        } catch (\Exception $e) {
            $io->error('Error calling AI: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
