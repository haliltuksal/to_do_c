<?php

namespace App\Service;

use App\Repository\DeveloperRepository;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SchedulerService
{
    private $em;
    private $developerRepository;
    private $taskRepository;
    /**
     * @var HttpClientInterface
     */
    private $client;

    public function __construct(
        EntityManagerInterface $em,
        DeveloperRepository $developerRepository,
        TaskRepository $taskRepository,
        HttpClientInterface $client
    ) {
        $this->em = $em;
        $this->developerRepository = $developerRepository;
        $this->taskRepository = $taskRepository;
        $this->client = $client;
    }

    /**
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws Exception
     */
    public function scheduleTasks(): int
    {
        $developers = $this->fetchDevelopers();
        $tasks = $this->fetchTasks();

        return $this->runSchedulingAlgorithm($developers, $tasks);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws Exception
     */
    private function fetchDevelopers(): array
    {
        $developersResponse = $this->client->request('GET', 'http://localhost:8001/api/developers');
        $developers = $developersResponse->toArray();

        if (empty($developers)) {
            throw new Exception('Geliştirici listesi boş.');
        }

        return $developers;
    }

    /**
     * @throws TransportExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws Exception
     */
    private function fetchTasks(): array
    {
        $tasksResponse = $this->client->request('GET', 'http://localhost:8001/api/tasks');
        $tasks = $tasksResponse->toArray();

        if (empty($tasks)) {
            throw new Exception('Görev listesi boş.');
        }

        return $tasks;
    }

    /**
     * @throws Exception
     */
    public function runSchedulingAlgorithm(array $developers, array $tasks): int
    {
        $developerCapacities = $this->initializeDeveloperCapacities($developers);
        usort($tasks, function ($a, $b) {
            return $b['difficulty'] - $a['difficulty'];
        });

        foreach ($tasks as $taskData) {
            $this->validateTaskData($taskData);
            $suitableDeveloperKey = $this->findSuitableDeveloper($developerCapacities, $taskData);

            if ($suitableDeveloperKey !== null) {
                $this->assignTaskToDeveloper($developerCapacities, $suitableDeveloperKey, $taskData);
            } else {
                throw new Exception('Görev için uygun geliştirici bulunamadı: ' . $taskData['name']);
            }
        }

        $this->em->flush();

        return max(array_column($developerCapacities, 'current_week'));
    }

    private function initializeDeveloperCapacities(array $developers): array
    {
        $developerCapacities = [];
        foreach ($developers as $developerData) {
            $this->validateDeveloperData($developerData);

            $developerEntity = $this->developerRepository->find($developerData['id']);
            if (!$developerEntity) {
                throw new Exception($developerData['id'] . " ID'ye ait Geliştirici bulunamadı");
            }

            $developerCapacities[$developerData['id']] = [
                'developerData' => $developerData,
                'developerEntity' => $developerEntity,
                'available_hours' => $developerData['weeklyWorkHours'],
                'current_week' => 1,
            ];
        }
        return $developerCapacities;
    }

    private function findSuitableDeveloper(array $developerCapacities, array $taskData): ?int
    {
        $suitableDeveloperKey = null;
        $minDifference = PHP_INT_MAX;

        foreach ($developerCapacities as $key => $capacity) {
            $developerEfficiency = $capacity['developerData']['efficiency'];
            $difference = abs($taskData['difficulty'] - $developerEfficiency);

            if ($difference < $minDifference) {
                $minDifference = $difference;
                $suitableDeveloperKey = $key;
            }
        }

        return $suitableDeveloperKey;
    }

    private function assignTaskToDeveloper(array &$developerCapacities, int $suitableDeveloperKey, array $taskData): void
    {
        $suitableDeveloper = &$developerCapacities[$suitableDeveloperKey];
        $adjustedDuration = $taskData['duration'] / $suitableDeveloper['developerData']['efficiency'];

        if ($suitableDeveloper['available_hours'] < $adjustedDuration) {
            $suitableDeveloper['current_week'] += 1;
            $suitableDeveloper['available_hours'] = $suitableDeveloper['developerData']['weeklyWorkHours'];
        }

        $taskAsObject = $this->taskRepository->find($taskData['id']);
        if ($taskAsObject) {
            $taskAsObject->setAssignedDeveloper($suitableDeveloper['developerEntity']);
            $taskAsObject->setWeek($suitableDeveloper['current_week']);

            $this->em->persist($taskAsObject);
        }

        $suitableDeveloper['available_hours'] -= $adjustedDuration;
    }

    private function validateDeveloperData(array $developerData): void
    {
        if (!isset($developerData['id'], $developerData['name'], $developerData['efficiency'], $developerData['weeklyWorkHours'])) {
            throw new Exception('Geliştirici verisi eksik alanlara sahip.');
        }

        if (!is_int($developerData['id']) || $developerData['id'] <= 0) {
            throw new Exception('Geliştirici ID geçersiz.');
        }

        if (!is_string($developerData['name']) || empty($developerData['name'])) {
            throw new Exception('Geliştirici adı geçersiz.');
        }

        if (!is_int($developerData['efficiency']) || $developerData['efficiency'] <= 0) {
            throw new Exception('Geliştirici verimliliği geçersiz.');
        }

        if (!is_int($developerData['weeklyWorkHours']) || $developerData['weeklyWorkHours'] <= 0) {
            throw new Exception('Geliştirici haftalık çalışma saati geçersiz.');
        }
    }

    private function validateTaskData(array $taskData): void
    {
        if (!isset($taskData['id'], $taskData['name'], $taskData['duration'], $taskData['difficulty'])) {
            throw new Exception('Görev verisi eksik alanlara sahip.');
        }

        if (!is_int($taskData['id']) || $taskData['id'] <= 0) {
            throw new Exception('Görev ID geçersiz.');
        }

        if (!is_string($taskData['name']) || empty($taskData['name'])) {
            throw new Exception('Görev adı geçersiz.');
        }

        if (!is_int($taskData['duration']) || $taskData['duration'] <= 0) {
            throw new Exception('Görev süresi geçersiz.');
        }

        if (!is_int($taskData['difficulty']) || $taskData['difficulty'] <= 0) {
            throw new Exception('Görev zorluk derecesi geçersiz.');
        }
    }
}
