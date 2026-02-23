<?php

namespace App\Entity;

use App\Entity\Enum\ReservationType;
use App\Entity\Enum\ReservationStatus;
use App\Repository\ReservationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ReservationRepository::class)]
class Reservation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'reservations')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'L\'utilisateur est requis')]
    private ?User $user = null;

    #[ORM\Column(type: 'string', enumType: ReservationType::class)]
    #[Assert\NotNull(message: 'Le type de réservation est requis')]
    private ?ReservationType $reservationType = null;

    #[ORM\Column]
    #[Assert\NotNull(message: 'L\'ID de réservation est requis')]
    #[Assert\Positive(message: 'L\'ID doit être positif')]
    private ?int $reservationId = null;

    #[ORM\Column]
    #[Assert\NotNull(message: 'Le prix total est requis')]
    #[Assert\Positive(message: 'Le prix doit être positif')]
    private ?float $totalPrice = null;

    #[ORM\Column(type: 'string', enumType: ReservationStatus::class)]
    private ?ReservationStatus $status = ReservationStatus::PENDING;

    #[ORM\Column(type: 'date_immutable')]
    #[Assert\NotNull(message: 'La date de début est requise')]
    private ?\DateTimeImmutable $dateFrom = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $dateTo = null;

    #[ORM\Column]
    #[Assert\NotNull(message: 'Le nombre de personnes est requis')]
    #[Assert\Positive(message: 'Le nombre de personnes doit être au moins 1')]
    private ?int $numberOfPersons = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $details = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\OneToOne(mappedBy: 'reservation', cascade: ['persist', 'remove'])]
    private ?PaymentReservation $paymentReservation = null;

    // Virtual relationships to other modules (not stored in database)
    // These are accessed via reservationType and reservationId
    private ?Hebergement $hebergement = null;
    private ?Activity $activity = null;
    private ?Transport $transport = null;

    public function __construct()
    {
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
        $this->status = ReservationStatus::PENDING;
        $this->dateFrom = $now;
        $this->numberOfPersons = 1;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getReservationType(): ?ReservationType
    {
        return $this->reservationType;
    }

    public function setReservationType(ReservationType $reservationType): self
    {
        $this->reservationType = $reservationType;
        return $this;
    }

    public function getReservationId(): ?int
    {
        return $this->reservationId;
    }

    public function setReservationId(int $reservationId): self
    {
        $this->reservationId = $reservationId;
        return $this;
    }

    public function getTotalPrice(): ?float
    {
        return $this->totalPrice;
    }

    public function setTotalPrice(float $totalPrice): self
    {
        $this->totalPrice = $totalPrice;
        return $this;
    }

    public function getStatus(): ?ReservationStatus
    {
        return $this->status;
    }

    public function setStatus(ReservationStatus $status): self
    {
        $this->status = $status;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getDateFrom(): ?\DateTimeImmutable
    {
        return $this->dateFrom;
    }

    public function setDateFrom(\DateTimeImmutable $dateFrom): self
    {
        $this->dateFrom = $dateFrom;
        return $this;
    }

    public function getDateTo(): ?\DateTimeImmutable
    {
        return $this->dateTo;
    }

    public function setDateTo(?\DateTimeImmutable $dateTo): self
    {
        $this->dateTo = $dateTo;
        return $this;
    }

    public function getNumberOfPersons(): ?int
    {
        return $this->numberOfPersons;
    }

    public function setNumberOfPersons(int $numberOfPersons): self
    {
        $this->numberOfPersons = $numberOfPersons;
        return $this;
    }

    public function getDetails(): ?array
    {
        return $this->details;
    }

    public function setDetails(?array $details): self
    {
        $this->details = $details;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getPaymentReservation(): ?PaymentReservation
    {
        return $this->paymentReservation;
    }

    public function setPaymentReservation(?PaymentReservation $paymentReservation): self
    {
        if ($paymentReservation === null && $this->paymentReservation !== null) {
            $this->paymentReservation->setReservation(null);
        }

        if ($paymentReservation !== null && $paymentReservation->getReservation() !== $this) {
            $paymentReservation->setReservation($this);
        }

        $this->paymentReservation = $paymentReservation;
        return $this;
    }

    // Helper methods for polymorphic relationships
    public function getHebergement(): ?Hebergement
    {
        return $this->hebergement;
    }

    public function setHebergement(?Hebergement $hebergement): self
    {
        $this->hebergement = $hebergement;
        if ($hebergement) {
            $this->reservationType = ReservationType::HEBERGEMENT;
            $this->reservationId = $hebergement->getId();
        }
        return $this;
    }

    public function getActivity(): ?Activity
    {
        return $this->activity;
    }

    public function setActivity(?Activity $activity): self
    {
        $this->activity = $activity;
        if ($activity) {
            $this->reservationType = ReservationType::ACTIVITY;
            $this->reservationId = $activity->getId();
        }
        return $this;
    }

    public function getTransport(): ?Transport
    {
        return $this->transport;
    }

    public function setTransport(?Transport $transport): self
    {
        $this->transport = $transport;
        if ($transport) {
            $this->reservationType = ReservationType::TRANSPORT;
            $this->reservationId = $transport->getId();
        }
        return $this;
    }
}
