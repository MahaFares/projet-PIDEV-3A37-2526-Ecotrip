<?php

namespace App\Entity;

use App\Repository\PaiementRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PaiementRepository::class)]
#[ORM\Table(name: 'paiement')]
class Paiement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_paiement', type: 'integer')]
    private ?int $idPaiement = null;

    #[ORM\ManyToOne(targetEntity: Commande::class, inversedBy: 'paiements')]
    #[ORM\JoinColumn(name: 'commande_id', referencedColumnName: 'id')]
    #[Assert\NotNull(message: 'La commande est obligatoire.')]
    private ?Commande $commande = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Assert\NotBlank(message: 'Le montant est obligatoire.')]
    #[Assert\Regex(pattern: '/^\d+(\.\d{1,2})?$/', message: 'Le montant doit être un nombre décimal valide.')]
    #[Assert\GreaterThan(value: 0, message: 'Le montant doit être strictement positif.')]
    private ?string $montant = null;

    #[ORM\Column(name: 'methode_paiement', length: 50)]
    #[Assert\NotBlank(message: 'La méthode de paiement est obligatoire.')]
    #[Assert\Choice(
        choices: ['Par carte', 'Par espèces'],
        message: 'La méthode de paiement doit être "Par carte" ou "Par espèces".'
    )]
    #[Assert\Length(max: 50)]
    #[Assert\Type('string')]
    private ?string $methodePaiement = null;

    #[ORM\Column(name: 'date_paiement', type: Types::DATETIME_MUTABLE)]
    #[Assert\NotNull(message: 'La date de paiement est obligatoire.')]
    private ?\DateTimeInterface $datePaiement = null;

    public function getIdPaiement(): ?int
    {
        return $this->idPaiement;
    }

    public function getCommande(): ?Commande
    {
        return $this->commande;
    }

    public function setCommande(?Commande $commande): static
    {
        $this->commande = $commande;

        return $this;
    }

    public function getMontant(): ?string
    {
        return $this->montant;
    }

    public function setMontant(string $montant): static
    {
        $this->montant = $montant;

        return $this;
    }

    public function getMethodePaiement(): ?string
    {
        return $this->methodePaiement;
    }

    public function setMethodePaiement(string $methodePaiement): static
    {
        $this->methodePaiement = $methodePaiement;

        return $this;
    }

    public function getDatePaiement(): ?\DateTimeInterface
    {
        return $this->datePaiement;
    }

    public function setDatePaiement(\DateTimeInterface $datePaiement): static
    {
        $this->datePaiement = $datePaiement;

        return $this;
    }
}