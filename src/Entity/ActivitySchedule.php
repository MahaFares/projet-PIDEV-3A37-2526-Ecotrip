<?php

namespace App\Entity;

use App\Repository\ActivityScheduleRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ActivityScheduleRepository::class)]
class ActivitySchedule
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'datetime')]
    #[Assert\NotNull(message: 'La date de début est requise')]
    #[Assert\GreaterThan(value: 'now', message: 'La date de début doit être dans le futur')]
    private ?\DateTimeInterface $startAt = null;

    #[ORM\Column(type: 'datetime')]
    #[Assert\NotNull(message: 'La date de fin est requise')]
    #[Assert\GreaterThan(propertyPath: 'startAt', message: 'La date de fin doit être après la date de début')]
    private ?\DateTimeInterface $endAt = null;

    #[ORM\Column]
    #[Assert\NotNull(message: 'Le nombre de places disponibles est requis')]
    #[Assert\Positive(message: 'Le nombre de places doit être positif')]
    #[Assert\Range(min: 1, notInRangeMessage: 'Au moins 1 place doit être disponible')]
    private ?int $availableSpots = null;

    #[ORM\ManyToOne(targetEntity: Activity::class, inversedBy: 'schedules')]  // ← Add targetEntity
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Une activité doit être associée')]
    private ?Activity $activity = null;
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStartAt(): ?\DateTimeInterface
    {
        return $this->startAt;
    }

    public function setStartAt(\DateTimeInterface $v): self
    {
        $this->startAt = $v;

        return $this;
    }

    public function getEndAt(): ?\DateTimeInterface
    {
        return $this->endAt;
    }

    public function setEndAt(\DateTimeInterface $v): self
    {
        $this->endAt = $v;

        return $this;
    }

    public function getAvailableSpots(): ?int
    {
        return $this->availableSpots;
    }

    public function setAvailableSpots(int $v): self
    {
        $this->availableSpots = $v;

        return $this;
    }

    public function getActivity(): ?Activity
    {
        return $this->activity;
    }

    public function setActivity(Activity $a): self
    {
        $this->activity = $a;

        return $this;
    }
}
