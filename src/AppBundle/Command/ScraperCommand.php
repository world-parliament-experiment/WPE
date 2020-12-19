<?php

// src/Command/ScraperCommand.php

namespace AppBundle\Command;

#use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

class ScraperCommand extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'app:scrape';

    protected function configure()
    {
        $this
        // the short description shown while running "php bin/console list"
        ->setDescription('Creates a new WPE initiative');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $initiative = new Initiative();

        $initiative->setState(InitiativeEnum::STATE_DRAFT);
        $initiative->setType(InitiativeEnum::TYPE_FUTURE);
        $initiative->setTitle("A new Initiative");
        $initiative->setDescription("This is an initiave to populate the global voting platform aka World Parliament Experiment with scraped data of real-world parliamentary bodies");
        $initiative->setCreatedBy("TheArchitectOfTheGods");
        $initiative->setDuration("7");

        $em = $this->getDoctrine()->getManager();

        $em->persist($initiative);
        $em->flush();


        // this method must return an integer number with the "exit status code"
        // of the command. You can also use these constants to make code more readable

        // return this if there was no problem running the command
        // (it's equivalent to returning int(0))
        //return Command::SUCCESS;

        // or return this if some error happened during the execution
        // (it's equivalent to returning int(1))
        // return Command::FAILURE;
    }
}
