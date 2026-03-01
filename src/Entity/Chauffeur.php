<?php

namespace App\Entity;

use App\Repository\ChauffeurRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ChauffeurRepository::class)]
class Chauffeur
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 80)]
    #[Assert\NotBlank(message: 'Le prénom est obligatoire.')]
    #[Assert\Length(min: 2, max: 80, minMessage: 'Le prénom doit contenir au moins {{ limit }} caractères.', maxMessage: 'Le prénom ne peut pas dépasser {{ limit }} caractères.')]
    private ?string $firstName = null;

    #[ORM\Column(length: 80)]
    #[Assert\NotBlank(message: 'Le nom est obligatoire.')]
    #[Assert\Length(min: 2, max: 80, minMessage: 'Le nom doit contenir au moins {{ limit }} caractères.', maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères.')]
    private ?string $lastName = null;

    #[ORM\Column(length: 30)]
    #[Assert\NotBlank(message: 'Le téléphone est obligatoire.')]
    #[Assert\Regex(pattern: '/^[0-9\+\s\-\(\)]{8,30}$/', message: 'Le numéro de téléphone n\'est pas valide.')]
    #[Assert\Length(max: 30)]
    private ?string $phone = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'Le numéro de permis est obligatoire.')]
    #[Assert\Length(min: 5, max: 50, minMessage: 'Le numéro de permis doit contenir au moins {{ limit }} caractères.', maxMessage: 'Le numéro de permis ne peut pas dépasser {{ limit }} caractères.')]
    private ?string $licenseNumber = null;

    #[ORM\Column]
    #[Assert\NotNull(message: 'L\'années d\'expérience est obligatoire.')]
    #[Assert\Range(min: 0, max: 50, notInRangeMessage: 'L\'expérience doit être entre {{ min }} et {{ max }} années.')]
    private ?int $experience = null;

    #[ORM\Column]
    #[Assert\NotNull(message: 'La note est obligatoire.')]
    #[Assert\Range(min: 0, max: 5, notInRangeMessage: 'La note doit être entre {{ min }} et {{ max }}.')]
    private ?float $rating = null;

    /**
     * @var Collection<int, Transport>
     */
    #[ORM\OneToMany(mappedBy: 'chauffeur', targetEntity: Transport::class)]
    private Collection $transports;

    public function __construct()
    {
        $this->transports = new ArrayCollection();
        $this->experience = 0;
        $this->rating = 0.0;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;
        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): static
    {
        $this->phone = $phone;
        return $this;
    }

    public function getLicenseNumber(): ?string
    {
        return $this->licenseNumber;
    }

    public function setLicenseNumber(string $licenseNumber): static
    {
        $this->licenseNumber = $licenseNumber;
        return $this;
    }

    public function getExperience(): ?int
    {
        return $this->experience;
    }

    public function setExperience(int $experience): static
    {
        $this->experience = $experience;
        return $this;
    }

    public function getRating(): ?float
    {
        return $this->rating;
    }

    public function setRating(float $rating): static
    {
        $this->rating = $rating;
        return $this;
    }

    /**
     * @return Collection<int, Transport>
     */
    public function getTransports(): Collection
    {
        return $this->transports;
    }

    public function addTransport(Transport $transport): static
    {
        if (!$this->transports->contains($transport)) {
            $this->transports->add($transport);
            $transport->setChauffeur($this);
        }
        return $this;
    }

    public function removeTransport(Transport $transport): static
    {
        if ($this->transports->removeElement($transport)) {
            if ($transport->getChauffeur() === $this) {
                $transport->setChauffeur(null);
            }
        }
        return $this;
    }

    public function getFullName(): string
    {
        return trim($this->firstName . ' ' . $this->lastName);
    }

    public function __toString(): string
    {
        return $this->getFullName();
    }
}
