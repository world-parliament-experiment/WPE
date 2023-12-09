<?php

namespace App\DataFixtures;

use Symfony\Component\String\Slugger\AsciiSlugger;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
//use Nelmio\Alice\Fixtures;
use Nelmio\Alice\Loader\NativeLoader;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {      
        $loader = new NativeLoader();    
        $objectSet=$loader->loadFile(__DIR__.'/Fixtures.yml')->getObjects();
        foreach($objectSet as $reference => $object) {
            $manager->persist($object);
        }
        $manager->flush();
        
    }
}
