<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Doctrine\ORM\EntityManagerInterface;
use AppBundle\Entity\Category;


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
            ))
            ->setHelp(<<<'EOT'
The <info>wpe:category:create</info> command creates a new category:

  <info>php %command.full_name% Deutschland </info>

EOT
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getArgument('name');
        $category = new Category();

        $category->setName($name);
        $this->em->persist($category);
        $this->em->flush();
                        
        $output->writeln('Saved new category with name '.$category->getName());  

    }
}
