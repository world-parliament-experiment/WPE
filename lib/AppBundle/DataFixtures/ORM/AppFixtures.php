<?php

namespace AppBundle\DataFixtures\ORM;

use AppBundle\Entity\Initiative;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\Loader\YamlFileLoader;
use Doctrine\Persistence\ObjectManager;
use Nelmio\Alice\Fixtures;

class AppFixtures extends Fixture
{

    /**
     * AppFixtures constructor.
     */
    public function __construct()
    {
        mt_srand($this->makeSeed());
    }

    public function load(ObjectManager $manager)
    {
        $objects = Fixtures::load(
            __DIR__.'/fixtures.yml',
            $manager,
            [
                'providers' => [$this]
            ]
        );
    }

    public function calcDateTimeByInitiative(Initiative $initiative)
    {
        $startDate = $initiative->getCreatedAt();
        return \Faker\Provider\DateTime::dateTimeBetween($startDate, 'now');
    }

    public function testObjectMethod(Initiative $initiative)
    {
        /** @var Initiative $initiative */
        return $initiative->getTitle();
    }

    private function makeSeed()
    {
        list($usec, $sec) = explode(' ', microtime());
        return $sec + $usec * 1000000;
    }

}