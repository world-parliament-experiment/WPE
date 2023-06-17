<?php

namespace AppBundle\Command;

use AppBundle\Enum\VotingEnum;
use AppBundle\Service\VotingManager;
use Psr\Log\LoggerInterface;
// use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ActivateVotingsCommand extends Command
{
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var VotingManager
     */
    private $manager;

    /**
     * ActivateVotingsCommand constructor.
     * @param VotingManager $manager
     * @param LoggerInterface $logger
     */
    public function __construct(VotingManager $manager, LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->manager = $manager;

        parent::__construct();
    }


    /**
     * {@inheritdoc}
     */
    protected function configure()
    {

        $this
            ->setName('tgde:votings:activate')
            ->setDescription('Activate published future votes')
            ->setDefinition(array(
                new InputOption('future', 'f', InputOption::VALUE_NONE, 'future votings'),
                new InputOption('current', 'c', InputOption::VALUE_NONE, 'current votings'),
                new InputOption('debug', 'd', InputOption::VALUE_NONE, 'debug output'),
            ));

    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $future = $input->getOption('future');
        $current = $input->getOption('current');
        $debug = $input->getOption('debug');

        $output->writeln("Activate votings ... ");

        if ($debug) {
            $output->writeln("with debug output...");
        }

        if ($future) {
            $votings = $this->manager->activateFutureVotings($debug);
            $message = 'Activated ' . $votings . ' future votings ...';
            $output->writeln($message);
        }

        if ($current) {
            $votings = $this->manager->activateCurrentVotings($debug);
            $message = 'Activated ' . $votings . ' current votings ... ';
            $output->writeln($message);
        }

        $this->logger->info($message);
        return 1;
    }
}
