<?php

namespace App\Command;

use App\Service\SchedulerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

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
        $this
            ->setDescription('Görevleri yükler ve geliştiricilere atar.')
            ->setHelp('Bu komut, tüm geliştiricilere mevcut görevleri verimli şekilde atamak için kullanılır.');
    }

    /**
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $totalWeeks = $this->schedulerService->scheduleTasks();

            $io->success('Görevler başarıyla geliştiricilere atandı.');
            $io->text('Bütün işler ' . $totalWeeks . ' haftada tamamlanacaktır.');

            return 0;
        } catch (\Exception $exception) {
            $io->error($exception->getMessage());

            return 1;
        }
    }
}
