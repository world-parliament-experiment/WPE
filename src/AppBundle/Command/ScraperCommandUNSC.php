<?php

namespace AppBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use AppBundle\Entity\Initiative;
use AppBundle\Entity\Category;
use AppBundle\Enum\InitiativeEnum;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class ScraperCommandUNSC extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'wpe:scrape:unsc';

    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct();
        $this->em = $em;
    }
    
    protected function configure()
    {
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('Creates new WPE initiatives from scraped content')
            ->addArgument('user', InputArgument::REQUIRED, 'The User under whose name the initiatives shall be created')
            ->addArgument('category', InputArgument::REQUIRED, 'The Category in which name the initiatives shall be created')
            ->addOption(
                'delete',
                'd',
                InputOption::VALUE_NONE,
                'Delete existing entries in the same category created by the same user'
            )
            ->addOption(
                'update',
                'u',
                InputOption::VALUE_NONE,
                'Update/Create new entries in the category with the given user'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $user= $this->em->getRepository('AppBundle\Entity\User')->findOneBy(array('username' => $input->getArgument('user')));
        $category = $this->em->getRepository('AppBundle\Entity\Category')->findOneBy(array('name' => $input->getArgument('category')));
        
        if ($input->getOption('delete') === true) {
            $del_initiatives = $this->em->getRepository('AppBundle\Entity\Initiative')->findBy(array('createdBy' => $user, 'category' => $category));
            foreach ($del_initiatives as $deletion) {
                $delid = $deletion->getId();
                $this->em->remove($deletion);
                $this->em->flush();
                echo ('Deleted initiave with id '.$delid);
                // return new Response;
            }   
        }

        if ($input->getOption('update') === true) {

            $process = new Process('python3 scrape_unsc.py');
            $process->run();
            
            // executes after the command finishes
            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }
            
            $contents = $process->getOutput();
            $contentstring = explode(",", $contents);

            $NewEntry = [];
            for ($i = 0; $i < count($contentstring); $i++) {
                $NewEntry[$contentstring[$i]] = $contentstring[++$i];
            }
            
            foreach ($NewEntry as $title => $desc) {
                
                $initiative = new Initiative();

                $initiative->setState(InitiativeEnum::STATE_ACTIVE);
                $initiative->setType(InitiativeEnum::TYPE_FUTURE);
                $initiative->setCategory($category);

                //title
                $title = str_replace("'", "", ($title));
                $title = str_replace("[", "", ($title));
                $initiative->setTitle($title);
            
                //CreatedBy and Duration
                $initiative->setCreatedBy($user);
                $initiative->setDuration("7");

                //Description
                $desc = str_replace("\\n", " <br /> ", ($desc));
                $desc = str_replace("]", "", ($desc));
                $url_regex = '~(?:http|ftp)s?://(?:www\.)?([a-z0-9.-]+\.[a-z]{2,3}(?:/\S*)?)~i';
                $desc = preg_replace($url_regex, '<a href="$0" rel="nofollow">$1</a>', $desc);
                $desc = str_replace("'", "", ($desc));
                
                $checkdata = $this->em->getRepository('AppBundle\Entity\Initiative')->findOneBy(array('description' => $desc)); //existing initiatives
                
                if(!is_null($checkdata)) {
                    $duplicate = $checkdata->getDescription();
                } else {
                    $duplicate = "";
                }
                
                
                if ($desc == $duplicate ) {
                    // echo $checkdata->getDescription()."\n";
                    continue;
                } else {
                    $initiative->setDescription($desc);
                    $this->em->persist($initiative);
                    $this->em->flush();
                                    
                    echo $title."\n";
                    echo $desc."\n";
                    $output->writeln('Saved new initiave with id '.$initiative->getId());            
                    
                } //persist

            } //new entries

        } //update option

        // return new Response('Saved new initiave with id '.$initiative->getId());

        // this method must return an integer number with the "exit status code"
        // of the command. You can also use these constants to make code more readable

        // return this if there was no problem running the command
        // (it's equivalent to returning int(0))
        //return int(0);

        // or return this if some error happened during the execution
        // (it's equivalent to returning int(1))
        // return Command::FAILURE;
    }
}
