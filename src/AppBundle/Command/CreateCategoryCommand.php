<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Doctrine\ORM\EntityManagerInterface;
use AppBundle\Entity\Category;
use AppBundle\Enum\CategoryEnum;


class CreateCategoryCommand extends ContainerAwareCommand
{
    
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct();
        $this->em = $em;
    }

    
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('wpe:category:create')
            ->setDescription('Create a new category.')
            ->setDefinition(array(
                new InputArgument('name', InputArgument::REQUIRED, 'The category name'),
                new InputArgument('type', InputArgument::REQUIRED, 'The category type'),
                new InputArgument('description', InputArgument::OPTIONAL, 'The category description'),
                new InputArgument('country', InputArgument::OPTIONAL, 'The country of the category'),
                new InputArgument('name_new', InputArgument::OPTIONAL, 'The new category name'),
                new InputArgument('type_new', InputArgument::OPTIONAL, 'The new type'),
                new InputArgument('description_new', InputArgument::OPTIONAL, 'The category description'),
                new InputArgument('country_new', InputArgument::OPTIONAL, 'The new country'),
                new InputOption('delete', 'd', InputOption::VALUE_NONE, 'Delete Category'),
                new InputOption('change', 'c', InputOption::VALUE_NONE, 'Change Category')
            ))
            ->setHelp(<<<'EOT'
The <info>wpe:category:create</info> command creates, delets or changes a category:

Create:
    php bin/console wpe:category:create Deutschland 2 DE
Delete 
    php bin/console wpe:category:create Deutschland 2 DE -d
Change
    php bin/console wpe:category:create Deutschland 2 "Bundestag" DE Germany 2 "Bundestag" DE -c   

Category Types:
    const TYPE_GLOBAL = 0;
    const TYPE_SUPRANATIONAL = 1;
    const TYPE_NATIONAL = 2;
    const TYPE_REGIONAL = 3;
    const TYPE_LOCAL = 4;
    
EOT
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getArgument('name');
        $description = $input->getArgument('description');
        $type = $input->getArgument('type');
        $country = $input->getArgument('country');
        
        $exist_category = $this->em->getRepository('AppBundle\Entity\Category')->findOneBy(array('name' => $name));
        
        if($exist_category) {

            if ($input->getOption('delete') === true) {
                try {
                    $this->em->remove($exist_category);
                    $this->em->flush();
                    $output->writeln('deleted category with name '.$exist_category->getName());
                }
                catch (\Exception $e) {
                    echo 'Caught exception: ',  $e->getMessage(), "\n";
                }
            }
            elseif ($input->getOption('change') === true)  {
                $name_new = $input->getArgument('name_new');
                $type_new = $input->getArgument('type_new');
                $country_new = $input->getArgument('country_new');
                $description_new = $input->getArgument('description_new');
                $exist_category->setName($name_new);
                $exist_category->setType($type_new);
                $exist_category->setCountry($country_new);
                $exist_category->setDescription($description_new);
                try {
                    $this->em->persist($exist_category);
                    $this->em->flush();
                    $output->writeln('Changed category with name '.$name.' to '.$exist_category->getName());  
                }
                catch (\Exception $e) {
                    echo 'Caught exception: ',  $e->getMessage(), "\n";
                }
            }
        }
        else {
            $category = new Category();
            $category->setName($name);
            $category->setType($type);
            $category->setCountry($country);
            $category->setDescription($description);
            $this->em->persist($category);
            $this->em->flush();
                            
            $output->writeln('Saved new category with name '.$category->getName());  
        }
    }
}
