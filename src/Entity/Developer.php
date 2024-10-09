<?php

namespace App\Entity;

use App\Repository\DeveloperRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=DeveloperRepository::class)
 */
class Developer
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups("developer:read")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups("developer:read")
     */
    private $name;

    /**
     * @ORM\Column(type="float")
     * @Groups("developer:read")
     */
    private $efficiency;

    /**
     * @ORM\Column(type="integer")
     * @Groups("developer:read")
     */
    private $weeklyWorkHours;

    /**
     * @ORM\OneToMany(targetEntity=Task::class, mappedBy="assignedDeveloper")
     */
    private $task;

    public function __construct()
    {
        $this->task = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getEfficiency(): ?float
    {
        return $this->efficiency;
    }

    public function setEfficiency(float $efficiency): self
    {
        $this->efficiency = $efficiency;

        return $this;
    }

    public function getWeeklyWorkHours(): ?int
    {
        return $this->weeklyWorkHours;
    }

    public function setWeeklyWorkHours(int $weeklyWorkHours): self
    {
        $this->weeklyWorkHours = $weeklyWorkHours;

        return $this;
    }

    /**
     * @return Collection<int, Task>
     */
    public function getTask(): Collection
    {
        return $this->task;
    }

    public function addTask(Task $task): self
    {
        if (!$this->task->contains($task)) {
            $this->task[] = $task;
            $task->setAssignedDeveloper($this);
        }

        return $this;
    }

    public function removeTask(Task $task): self
    {
        if ($this->task->removeElement($task)) {
            if ($task->getAssignedDeveloper() === $this) {
                $task->setAssignedDeveloper(null);
            }
        }

        return $this;
    }
}
