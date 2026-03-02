<?php

namespace App\Entity;

use App\Enum\PaymentMethod;
use App\Enum\PaymentStatus;
use App\Repository\PaymentReservationRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PaymentReservationRepository::class)]
class PaymentReservation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'paymentReservation')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'La réservation est requise')]
    private ?Reservation $reservation = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Assert\NotNull(message: 'Le montant est requis')]
    #[Assert\Positive(message: 'Le montant doit être positif')]
    private ?string  $amount = null;

    #[ORM\Column(type: 'string', enumType: PaymentMethod::class)]
    #[Assert\NotNull(message: 'La méthode de paiement est requise')]
    private ?PaymentMethod $paymentMethod = null;

    #[ORM\Column(type: 'string', enumType: PaymentStatus::class)]
    private ?PaymentStatus $paymentStatus = PaymentStatus::PENDING;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $transactionId = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $paidAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->paymentStatus = PaymentStatus::PENDING;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReservation(): ?Reservation
    {
        return $this->reservation;
    }

    public function setReservation(?Reservation $reservation): self
    {
        $this->reservation = $reservation;
        return $this;
    }

    public function getAmount(): ?string 
    {
        return $this->amount;
    }

    public function setAmount(string  $amount): self
    {
        $this->amount = $amount;
        return $this;
    }

    public function getPaymentMethod(): ?PaymentMethod
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(PaymentMethod $paymentMethod): self
    {
        $this->paymentMethod = $paymentMethod;
        return $this;
    }

    public function getPaymentStatus(): ?PaymentStatus
    {
        return $this->paymentStatus;
    }

    public function setPaymentStatus(PaymentStatus $paymentStatus): self
    {
        $this->paymentStatus = $paymentStatus;
        
        if ($paymentStatus === PaymentStatus::COMPLETED && $this->paidAt === null) {
            $this->paidAt = new \DateTimeImmutable();
        }
        
        return $this;
    }

    public function getTransactionId(): ?string
    {
        return $this->transactionId;
    }

    public function setTransactionId(?string $transactionId): self
    {
        $this->transactionId = $transactionId;
        return $this;
    }

    public function getPaidAt(): ?\DateTimeImmutable
    {
        return $this->paidAt;
    }

    public function setPaidAt(?\DateTimeImmutable $paidAt): self
    {
        $this->paidAt = $paidAt;
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
}
