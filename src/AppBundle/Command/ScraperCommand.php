<?php

namespace AppBundle\Command;

use Symfony\Component\Console\Command\Command;
//use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use AppBundle\Entity\Initiative;
use AppBundle\Entity\Category;
use AppBundle\Enum\InitiativeEnum;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class ScraperCommand extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'tgde:scrape';

    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct();
        $this->em = $em;
    }
    
    protected function configure()
    {
        $this
        // the short description shown while running "php bin/console list"
        ->setDescription('Creates a new WPE initiative');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

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
        
        $user= $this->em->getRepository('AppBundle\Entity\User')->findOneBy(array('username' => "fjast"));
        $category = $this->em->getRepository('AppBundle\Entity\Category')->findOneBy(array('name' => "Security Policy"));
        
        foreach ($NewEntry as $title => $desc) {
            
            $initiative = new Initiative();

            $initiative->setState(InitiativeEnum::STATE_ACTIVE);
            $initiative->setType(InitiativeEnum::TYPE_FUTURE);
            $initiative->setTitle($title);
        
            $initiative->setCreatedBy($user);
            $initiative->setDuration("7");

            
            $initiative->setCategory($category);
            
            
            //regular expression to identify URLs in the description
            $reg_exUrl = "/(http|https|ftp|ftps)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/";
            if(preg_match($reg_exUrl, $desc, $url)) {

            // make the urls in the descriptions hyper links
                $desc = preg_replace($reg_exUrl, '<a href="'.$url[0].'" rel="nofollow">'.$url[0].'</a>', $desc);
            }
            $desc = str_replace("\\n", "<br />", ($desc));
            $initiative->setDescription($desc);
            
            $this->em->persist($initiative);
            $this->em->flush();
            
            echo $title."\n";
            echo $desc."\n";
            $output->writeln('Saved new initiave with id '.$initiative->getId());
        }

/*         $del_initiatives = $this->em->getRepository('AppBundle\Entity\Initiative')->findBy(array('createdBy' => $user, 'category' => $category));
        foreach ($del_initiatives as $deletion) {
            $this->em->remove($deletion);
            $this->em->flush();
            echo ('Deleted initiave with id '.$deletion->getId());
            // return new Response;
        }
 */
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
