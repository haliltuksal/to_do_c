<?php

namespace App\Controller;

use App\Entity\Task;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PlanningController extends AbstractController
{
    /**
     * @Route("/", name="planning")
     */
    public function index(): Response
    {
        $tasks = $this->getDoctrine()->getRepository(Task::class)->findBy([], ['difficulty' => 'ASC']);

        $totalWeeks = 0;
        foreach ($tasks as $task) {
            if ($task->getWeek() > $totalWeeks) {
                $totalWeeks = $task->getWeek();
            }
        }

        return $this->render('planning/index.html.twig', [
            'tasks' => $tasks,
            'totalWeeks' => $totalWeeks,
        ]);
    }
}