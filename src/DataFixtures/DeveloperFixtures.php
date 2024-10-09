<?php

namespace App\DataFixtures;

use App\Entity\Developer;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class DeveloperFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $developers = [
            ['name' => 'DEV1', 'efficiency' => 1.0],
            ['name' => 'DEV2', 'efficiency' => 2.0],
            ['name' => 'DEV3', 'efficiency' => 3.0],
            ['name' => 'DEV4', 'efficiency' => 4.0],
            ['name' => 'DEV5', 'efficiency' => 5.0],
        ];

        foreach ($developers as $devData) {
            $developer = new Developer();
            $developer->setName($devData['name']);
            $developer->setEfficiency($devData['efficiency']);
            $developer->setWeeklyWorkHours(45);

            $manager->persist($developer);
        }

        $manager->flush();
    }
}