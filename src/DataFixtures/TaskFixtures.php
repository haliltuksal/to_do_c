<?php

namespace App\DataFixtures;

use App\Entity\Task;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class TaskFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $mockOnePath = __DIR__ . '/mocks/mock-one.json';
        $mockTwoPath = __DIR__ . '/mocks/mock-two.json';

        $mockOneData = json_decode(file_get_contents($mockOnePath), true);
        $mockTwoData = json_decode(file_get_contents($mockTwoPath), true);

        foreach ($mockOneData as $item) {
            $task = new Task();
            $task->setName('MockOne Task ' . $item['id']);
            $task->setDuration($item['estimated_duration']);
            $task->setDifficulty($item['value']);
            $task->setProvider('MockOne');

            $manager->persist($task);
        }

        foreach ($mockTwoData as $item) {
            $task = new Task();
            $task->setName('MockTwo Task ' . $item['id']);
            $task->setDuration($item['sure']);
            $task->setDifficulty($item['zorluk']);
            $task->setProvider('MockTwo');

            $manager->persist($task);
        }

        $manager->flush();
    }
}