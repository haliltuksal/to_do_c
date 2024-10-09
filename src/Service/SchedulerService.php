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
    )
    {
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
    public function scheduleTasks()
    {
        $developersResponse = $this->client->request('GET', 'http://localhost:8001/api/developers');
        $developers = $developersResponse->toArray();

        if (empty($developers)) {
            throw new \Exception('Geliştirici listesi boş.');
        }

        $tasksResponse = $this->client->request('GET', 'http://localhost:8001/api/tasks');
        $tasks = $tasksResponse->toArray();
        if (empty($tasks)) {
            throw new \Exception('Görev listesi boş.');
        }

        return $this->runSchedulingAlgorithm($developers, $tasks);
    }


    /**
     * @throws Exception
     */
    public function runSchedulingAlgorithm($developers, $tasks)
    {
        if (empty($developers)) {
            throw new Exception('Geliştirici bulunamadı. Lütfen geliştiricileri veritabanına yükleyin.');
        }

        $developerCapacities = [];
        foreach ($developers as $developerData) {
            $this->validateDeveloperData($developerData);

            $developerEntity = $this->developerRepository->find($developerData['id']);
            if (!$developerEntity) {
                throw new Exception('Developer with id ' . $developerData['id'] . ' not found.');
            }

            $developerCapacities[$developerData['id']] = [
                'developerData' => $developerData, // API'den gelen dizi
                'developerEntity' => $developerEntity, // Veritabanından alınan Developer nesnesi
                'available_hours' => $developerData['weeklyWorkHours'],
                'current_week' => 1,
            ];
        }

        usort($tasks, function ($a, $b) {
            return $b['difficulty'] - $a['difficulty'];
        });

        foreach ($tasks as $taskData) {
            $this->validateTaskData($taskData);

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

            if ($suitableDeveloperKey !== null) {
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
            } else {
                throw new Exception('Görev için uygun geliştirici bulunamadı: ' . $taskData['name']);
            }
        }

        $this->em->flush();

        return max(array_column($developerCapacities, 'current_week'));
    }

    private function validateDeveloperData(array $developerData)
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

    private function validateTaskData(array $taskData)
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

