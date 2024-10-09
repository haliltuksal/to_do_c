<?php

namespace App\Entity;

use App\Entity\Traits\TimestampableTrait;
use App\Repository\TaskRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=TaskRepository::class)
 * @ORM\HasLifecycleCallbacks
 */
class Task
{
    use TimestampableTrait;
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups("task:read")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups("task:read")
     */
    private $provider;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups("task:read")
     */
    private $name;

    /**
     * @ORM\Column(type="float")
     * @Groups("task:read")
     */
    private $duration;

    /**
     * @ORM\Column(type="integer")
     * @Groups("task:read")
     */
    private $difficulty;

    /**
     * @ORM\ManyToOne(targetEntity=Developer::class, inversedBy="week")
     * @Groups("task:read")
     */
    private $assignedDeveloper;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Groups("task:read")
     */
    private $week;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProvider(): ?string
    {
        return $this->provider;
    }

    public function setProvider(string $provider): self
    {
        $this->provider = $provider;

        return $this;
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

    public function getDuration(): ?float
    {
        return $this->duration;
    }

    public function setDuration(float $duration): self
    {
        $this->duration = $duration;

        return $this;
    }

    public function getDifficulty(): ?int
    {
        return $this->difficulty;
    }

    public function setDifficulty(int $difficulty): self
    {
        $this->difficulty = $difficulty;

        return $this;
    }

    public function getAssignedDeveloper(): ?Developer
    {
        return $this->assignedDeveloper;
    }

    public function setAssignedDeveloper(?Developer $assignedDeveloper): self
    {
        $this->assignedDeveloper = $assignedDeveloper;

        return $this;
    }

    public function getWeek(): ?int
    {
        return $this->week;
    }

    public function setWeek(?int $week): self
    {
        $this->week = $week;

        return $this;
    }
}
