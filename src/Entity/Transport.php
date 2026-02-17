<?php

namespace App\Entity;
use Symfony\Component\Validator\Constraints as Assert;
use App\Repository\TransportRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TransportRepository::class)]
class Transport
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: "Le type de transport est obligatoire")]
    #[Assert\Length(
        min: 3,
        max: 100,
        minMessage: "Le type doit contenir au moins {{ limit }} caractères",
        maxMessage: "Le type ne peut pas dépasser {{ limit }} caractères"
    )]
    private ?string $type = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: "La capacité est obligatoire")]
    #[Assert\Positive(message: "La capacité doit être un nombre positif")]
    #[Assert\Range(
        min: 1,
        max: 500,
        notInRangeMessage: "La capacité doit être entre {{ min }} et {{ max }}"
    )]
    private ?int $capacite = null;

    #[ORM\Column(name: 'emissionco2')]
    #[Assert\NotBlank(message: "L'émission CO2 est obligatoire")]
    #[Assert\PositiveOrZero(message: "L'émission CO2 doit être positive ou zéro")]
    private ?float $emissionco2 = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, name: 'prixparpersonne')]
    #[Assert\NotBlank(message: "Le prix est obligatoire")]
    #[Assert\Positive(message: "Le prix doit être positif")]
    private ?string $prixparpersonne = null;
    #[ORM\Column]
    private ?bool $disponible = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\ManyToOne(inversedBy: 'transports')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?TransportCategory $category = null;

    #[ORM\ManyToOne(inversedBy: 'transports')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Chauffeur $chauffeur = null;

    /**
     * @var Collection<int, Trajet>
     */
    #[ORM\OneToMany(targetEntity: Trajet::class, mappedBy: 'transport')]
    private Collection $trajets;

    public function __construct()
    {
        $this->trajets = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getCapacite(): ?int
    {
        return $this->capacite;
    }

    public function setCapacite(int $capacite): static
    {
        $this->capacite = $capacite;

        return $this;
    }

    public function getEmissionco2(): ?float
    {
        return $this->emissionco2;
    }

    public function setEmissionco2(float $emissionco2): static
    {
        $this->emissionco2 = $emissionco2;

        return $this;
    }

    public function getPrixparpersonne(): ?string
    {
        return $this->prixparpersonne;
    }

    public function setPrixparpersonne(string $prixparpersonne): static
    {
        $this->prixparpersonne = $prixparpersonne;

        return $this;
    }

    public function isDisponible(): ?bool
    {
        return $this->disponible;
    }

    public function setDisponible(bool $disponible): static
    {
        $this->disponible = $disponible;

        return $this;
    }

    /**
     * @return Collection<int, Trajet>
     */
    public function getTrajets(): Collection
    {
        return $this->trajets;
    }

    public function addTrajet(Trajet $trajet): static
    {
        if (!$this->trajets->contains($trajet)) {
            $this->trajets->add($trajet);
            $trajet->setTransport($this);
        }

        return $this;
    }

    public function removeTrajet(Trajet $trajet): static
    {
        if ($this->trajets->removeElement($trajet)) {
            // set the owning side to null (unless already changed)
            if ($trajet->getTransport() === $this) {
                $trajet->setTransport(null);
            }
        }

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;
        return $this;
    }

    public function getCategory(): ?TransportCategory
    {
        return $this->category;
    }

    public function setCategory(?TransportCategory $category): static
    {
        $this->category = $category;
        return $this;
    }

    public function getChauffeur(): ?Chauffeur
    {
        return $this->chauffeur;
    }

    public function setChauffeur(?Chauffeur $chauffeur): static
    {
        $this->chauffeur = $chauffeur;
        return $this;
    }
}
