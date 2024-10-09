<?php

namespace App\Controller;

use App\Repository\DeveloperRepository;
use App\Repository\TaskRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @Route("/api")
 */
class ApiController extends AbstractController
{
    private $developerRepository;
    private $taskRepository;

    public function __construct(
        DeveloperRepository $developerRepository,
        TaskRepository $taskRepository
    )
    {
        $this->developerRepository = $developerRepository;
        $this->taskRepository = $taskRepository;
    }

    /**
     * @Route("/developers", methods={"GET"})
     */
    public function getDevelopers(SerializerInterface $serializer): JsonResponse
    {
        $developers = $this->developerRepository->findAll();

        $jsonData = $serializer->serialize($developers, 'json', ['groups' => 'developer:read']);

        return new JsonResponse($jsonData, 200, [], true);
    }

    /**
     * @Route("/tasks", methods={"GET"})
     */
    public function getTasks(SerializerInterface $serializer): JsonResponse
    {
        $tasks = $this->taskRepository->findUnassignedTasks();

        $jsonData = $serializer->serialize($tasks, 'json', ['groups' => 'task:read']);

        return new JsonResponse($jsonData, 200, [], true);
    }
}