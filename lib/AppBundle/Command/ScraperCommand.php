<?php

namespace AppBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use AppBundle\Entity\Initiative;
use AppBundle\Entity\Category;
use AppBundle\Entity\Voting;
use AppBundle\Enum\InitiativeEnum;
use AppBundle\Enum\VotingEnum;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Symfony\Component\String\Slugger\AsciiSlugger;
use DateTime;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use AppBundle\Service\SocialmediaPoster;


class ScraperCommand extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'wpe:scrape';
    protected static $_explchar = "', '";
    private $SocialmediaPoster;
    private $router;

    public function __construct(EntityManagerInterface $em, UrlGeneratorInterface $router, SocialmediaPoster $SocialmediaPoster)
    {
        parent::__construct();
        $this->em = $em;
        $this->SocialmediaPoster = $SocialmediaPoster;
        $this->router = $router;

    }

    protected function configure()
    {
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('Creates new WPE initiatives from scraped content')
            ->addArgument('country', InputArgument::REQUIRED, 'The country ISO code or organization code for the scraping script')
            ->addArgument('user', InputArgument::REQUIRED, 'The User under whose name the initiatives shall be created')
            ->addArgument('category', InputArgument::REQUIRED, 'The Category in which the initiatives shall be created')
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

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $country = $input->getArgument('country');
        $user = $this->em->getRepository('AppBundle\Entity\User')->findOneBy(array('username' => $input->getArgument('user')));
        $category = $this->em->getRepository('AppBundle\Entity\Category')->findOneBy(array('name' => $input->getArgument('category')));
        $explchar = self::$_explchar;
        $slugger = new AsciiSlugger();

        if ($input->getOption('delete') === true) {
            $del_initiatives = $this->em->getRepository('AppBundle\Entity\Initiative')->findBy(array('createdBy' => $user, 'category' => $category));
            foreach ($del_initiatives as $deletion) {
                $delid = $deletion->getId();
                $dvoting = $deletion->getFutureVoting();
                echo $deletion->getType();
                if ($dvoting) {
                    $votes_exist = $dvoting->getVotesTotal();
                    if (isset($votes_exist)) { // do not delete intiative if votes already exist
                        continue;
                    } else {
                        try{
                            $this->em->remove($dvoting);
                            $this->em->remove($deletion);
                            $this->em->flush();
                        }
                        catch (\Exception $e) {
                            echo 'Caught exception: ',  $e->getMessage(), "\n";
                            continue;
                        }
                        echo ('Deleted initiave with id '.$delid);
                    }
                    //delete inititive if no voting objects exist

                } else {
                    try{
                        $this->em->remove($deletion);
                        $this->em->flush();
                    }
                    catch (\Exception $e) {
                        echo 'Caught exception: ',  $e->getMessage(), "\n";
                        continue;
                    }
                    echo ('Deleted initiave with id '.$delid);
                }
            }
        }

        if ($input->getOption('update') === true) {

            $command = '/usr/bin/python3 '.dirname(__FILE__, 4).'/python/scrape_'.$country.'.py';
            $process = Process::fromShellCommandline($command);
            //var_dump($process->getCommandLine());
            $process->setTimeout(600);
            $process->mustRun();

            // executes after the command finishes
            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            $contents = $process->getOutput();
            $contentstring = explode("', '", $contents);

            $NewEntry = [];
            for ($i = 0; $i < count($contentstring); $i++) {
                $NewEntry[$contentstring[$i]] = $contentstring[++$i];
            }

            foreach ($NewEntry as $title => $desc) {

                //title
                $title = str_replace("'", "", ($title));
                $title = str_replace("[", "", ($title));
                $title = substr($title,0,255);

                //Description
                $desc = str_replace("\\n", " <br /> ", ($desc));
                $desc = str_replace("]", "", ($desc));
                $url_regex = '~(?:http|https|ftps)?://(?:www\.)?([a-z0-9.-]+\.[a-z0-9]{1,3}(?:/\S*)?)~i';
                $desc = preg_replace($url_regex, '<a href="$0" rel="nofollow" target="_blank">$1</a>', $desc);
                $desc = str_replace("'", "", ($desc));

                $checkdata = $this->em->getRepository('AppBundle\Entity\Initiative')->findOneBy(array('title' => $title)); //existing initiatives
                if(!is_null($checkdata)) {
                    $duplicate = $checkdata->getTitle();
                } else {
                    $duplicate = "";
                }

                if ($title == $duplicate ) {
                    // echo $checkdata->getDescription()."\n";
                    continue;
                } else { //persist new initiative and voting

                    $initiative = new Initiative();
                    $voting = New Voting();

                    $startdate = new DateTime("+2 minutes");
                    $enddate = new DateTime("+6 months");
                    $initiative->setCategory($category);
                    $initiative->setTitle($title);
                    $initiative->setDescription($desc);
                    $initiative->setCreatedBy($user);
                    $initiative->setDuration("7");
                    $initiative->setPublishedAt($startdate);
                    $initiative->setCreatedAt($startdate);
                    $initiative->setSlug($slugger);
                    $initiative->setType(InitiativeEnum::TYPE_FUTURE);
                    $initiative->setState(InitiativeEnum::STATE_ACTIVE);
    
                    // $startdate->modify("today 20:00");
    
                    // if ($startdate < new DateTime()) {
                    //     $startdate->modify("tomorrow 20:00");
                    // }
    
                    $voting->setStartdate($startdate);
                    $voting->setEnddate($enddate);
    
                    $voting->setState(VotingEnum::STATE_WAITING);
                    $voting->setType(VotingEnum::TYPE_FUTURE);
                    $voting->setInitiative($initiative);
    
                    $this->em->persist($voting);
                    $this->em->persist($initiative);
                    $this->em->flush();
                                    
                    echo $title."\n";
                    echo $desc."\n";

                    $output->writeln('Saved new initiave with id '.$initiative->getId());  
/*                     $message = "Endorse or discuss this new legislation proposal here:";
                    $source = $this->router->generate('initiative_show', ['id' => $initiative->getId(),'slug' => $initiative->getSlug(),], UrlGeneratorInterface::ABSOLUTE_URL);
                    $this->SocialmediaPoster->postUpdate($message,$source,$title); */
                    
                } //persist

            } //new entries

        } //update option

        // return new Response('Saved new initiave with id '.$initiative->getId());

        // this method must return an integer number with the "exit status code"
        // of the command. You can also use these constants to make code more readable

        // return this if there was no problem running the command
        // (it's equivalent to returning int(0))
        //return int(0);
        return Command::SUCCESS;

        // or return this if some error happened during the execution
        // (it's equivalent to returning int(1))
        // return Command::FAILURE;
    }
}
