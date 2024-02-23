<?php

namespace AppBundle\Command;

use AppBundle\Enum\VotingEnum;
use AppBundle\Service\VotingManager;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class EvaluateVotingsCommand extends Command
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
     * EvaluateVotingsCommand constructor.
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
            ->setName('tgde:votings:evaluate')
            ->setDescription('evaluate votings')
            ->setDefinition(array(
                new InputOption('future', 'f', InputOption::VALUE_NONE, 'future votings'),
                new InputOption('current', 'c', InputOption::VALUE_NONE, 'current votings'),
                new InputOption('randomize', 'r', InputOption::VALUE_NONE, 'randomize votes'),
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
        $randomize = $input->getOption('randomize');
        $debug = $input->getOption('debug');

        $output->writeln("Evaluate votings ... ");

        if ($randomize) {
            $output->writeln("with randomization...");
        }

        if ($debug) {
            $output->writeln("with debug output...");
        }

        if ($future) {
            $votings = $this->manager->evaluateFutureVotings($randomize, $debug);
            $message = 'Evaluated ' . $votings . ' future votings ...';
            $output->writeln($message);
        }

        if ($current) {
            $votings = $this->manager->evaluateCurrentVotings($randomize, $debug);
            $message = 'Evaluated ' . $votings . ' current votings ... ';
            $output->writeln($message);
        }
        return 1;
    }
}
