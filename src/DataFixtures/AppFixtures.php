<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Nelmio\Alice\Loader\NativeLoader;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {      
        $loader = new NativeLoader();    
        $objectSet = $loader->loadFile(__DIR__.'/Fixtures.yml')->getObjects();
        
        foreach($objectSet as $reference => $object) {
            if ($object instanceof User && $object->getPlainPassword()) {
                $hashedPassword = $this->passwordHasher->hashPassword(
                    $object,
                    $object->getPlainPassword()
                );
                $object->setPassword($hashedPassword);
            }
            $manager->persist($object);
        }
        $manager->flush();
    }
}
