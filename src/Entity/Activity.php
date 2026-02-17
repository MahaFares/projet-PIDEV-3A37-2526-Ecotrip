<?php

namespace App\Entity;

use App\Repository\ActivityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ActivityRepository::class)]
class Activity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    #[Assert\NotBlank(message: 'Le titre de l\'activité est requis')]
    #[Assert\Length(min: 3, max: 150, minMessage: 'Le titre doit contenir au moins 3 caractères', maxMessage: 'Le titre ne peut pas dépasser 150 caractères')]
    private ?string $title = null;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank(message: 'La description est requise')]
    #[Assert\Length(min: 10, max: 5000, minMessage: 'La description doit contenir au moins 10 caractères', maxMessage: 'La description ne peut pas dépasser 5000 caractères')]
    private ?string $description = null;

    #[ORM\Column]
    #[Assert\NotNull(message: 'Le prix est requis')]
    #[Assert\Positive(message: 'Le prix doit être un nombre positif')]
    #[Assert\Type('float', message: 'Le prix doit être un nombre')]
    private ?float $price = null;

    #[ORM\Column]
    #[Assert\NotNull(message: 'La durée est requise')]
    #[Assert\Positive(message: 'La durée doit être positive')]
    #[Assert\Range(min: 5, max: 1440, notInRangeMessage: 'La durée doit être entre 5 et 1440 minutes')]
    private ?int $durationMinutes = null;

    #[ORM\Column(length: 150)]
    #[Assert\NotBlank(message: 'Le lieu est requis')]
    #[Assert\Length(min: 3, max: 150, minMessage: 'Le lieu doit contenir au moins 3 caractères', maxMessage: 'Le lieu ne peut pas dépasser 150 caractères')]
    private ?string $location = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Positive(message: 'Le nombre de participants doit être positif')]
    #[Assert\Range(min: 1, notInRangeMessage: 'Au moins 1 participant requis')]
    private ?int $maxParticipants = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Length(max: 255, maxMessage: 'L\'URL de l\'image ne peut pas dépasser 255 caractères')]
    #[Assert\Regex(
    pattern: "/\.(jpg|jpeg|png|webp)$/i",
    message: "Le fichier doit être une image valide (jpg, jpeg, png, webp)")]
    private ?string $image = null;

    


    #[ORM\Column]
    private bool $isActive = true;

    #[ORM\ManyToOne(inversedBy: 'activities')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Vous devez sélectionner une catégorie')]
    private ?ActivityCategory $category = null;

    #[ORM\ManyToOne(inversedBy: 'activities')]
    private ?Guide $guide = null;

    #[ORM\OneToMany(mappedBy: 'activity', targetEntity: ActivitySchedule::class, cascade: ['remove'])]
    private Collection $schedules;

    public function __construct()
    {
        $this->schedules = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getTitle(): ?string { return $this->title; }
    public function setTitle(string $v): self { $this->title = $v; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(string $v): self { $this->description = $v; return $this; }

    public function getPrice(): ?float { return $this->price; }
    public function setPrice(float $v): self { $this->price = $v; return $this; }

    public function getDurationMinutes(): ?int { return $this->durationMinutes; }
    public function setDurationMinutes(int $v): self { $this->durationMinutes = $v; return $this; }

    public function getLocation(): ?string { return $this->location; }
    public function setLocation(string $v): self { $this->location = $v; return $this; }

    public function getMaxParticipants(): ?int { return $this->maxParticipants; }
    public function setMaxParticipants(?int $v): self { $this->maxParticipants = $v; return $this; }

    public function getImage(): ?string { return $this->image; }
    public function setImage(?string $v): self { $this->image = $v; return $this; }

    public function isActive(): bool { return $this->isActive; }
    public function setIsActive(bool $v): self { $this->isActive = $v; return $this; }

    public function getCategory(): ?ActivityCategory { return $this->category; }
    public function setCategory(ActivityCategory $c): self { $this->category = $c; return $this; }

    public function getGuide(): ?Guide { return $this->guide; }
    public function setGuide(?Guide $g): self { $this->guide = $g; return $this; }

    public function getSchedules(): Collection { return $this->schedules; }

    public function setImage360(?string $image360): self
    {
        $this->image360 = $image360;
        return $this;
    }

    public function getLatitude(): ?string
    {
        return $this->latitude;
    }

    public function setLatitude(?string $latitude): self
    {
        $this->latitude = $latitude;
        return $this;
    }

    public function getLongitude(): ?string
    {
        return $this->longitude;
    }

    public function setLongitude(?string $longitude): self
    {
        $this->longitude = $longitude;
        return $this;
    }



}
