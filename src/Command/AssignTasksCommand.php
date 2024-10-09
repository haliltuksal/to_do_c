<?php

namespace App\Command;

use App\Service\SchedulerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AssignTasksCommand extends Command
{
    protected static $defaultName = 'app:assign-tasks';

    private $entityManager;
    private $schedulerService;

    public function __construct(EntityManagerInterface $entityManager, SchedulerService $schedulerService)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->schedulerService = $schedulerService;
    }

    protected function configure()
    {
        $this->setDescription('Görevleri yükler ve geliştiricilere atar.');
    }

    /**
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $totalWeeks = $this->schedulerService->scheduleTasks();

            $output->writeln('Görevler başarıyla geliştiricilere atandı.');
            $output->writeln('Bütün işler ' . $totalWeeks . ' haftada tamamlanacaktır.');
        }catch (\Exception $exception){
            $output->writeln("<error>".$exception->getMessage()."</error>");
        }
    }
}

