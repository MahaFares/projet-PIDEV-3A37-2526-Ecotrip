<?php

namespace App\Entity;

use App\Repository\CategorieRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: CategorieRepository::class)]
#[ORM\Table(name: 'categorie')]
#[Assert\Callback('validate')]
class Categorie
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id', type: 'integer')]
    private ?int $idCategorie = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Le nom est obligatoire.')]
    #[Assert\Choice(choices: ['A louer', 'A vendre'], message: 'Le nom doit être "A louer" ou "A vendre".')]
    #[Assert\Length(max: 100, maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères.')]
    #[Assert\Type('string')]
    private ?string $nom = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\Type('string')]
    private ?string $description = null;

    /**
     * @var Collection<int, Produit>
     */
    #[ORM\OneToMany(targetEntity: Produit::class, mappedBy: 'categorie')]
    private Collection $produits;

    public function __construct()
    {
        $this->produits = new ArrayCollection();
    }

    /**
     * Validation personnalisée au niveau de la classe
     * Si l'état est "A louer", la description ne peut pas être vide
     */
    public function validate(ExecutionContextInterface $context): void
    {
        // Vérifier si l'état est "A louer"
        if ($this->nom === 'A louer') {
            // Vérifier si la description est vide ou contient uniquement des espaces
            if ($this->description === null || trim($this->description) === '') {
                $context->buildViolation('La description ne peut pas être vide pour l\'état "A louer".')
                    ->atPath('description')
                    ->addViolation();
            }
        }
    }

    public function getId(): ?int
    {
        return $this->idCategorie;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return Collection<int, Produit>
     */
    public function getProduits(): Collection
    {
        return $this->produits;
    }

    public function addProduit(Produit $produit): static
    {
        if (!$this->produits->contains($produit)) {
            $this->produits->add($produit);
            $produit->setCategorie($this);
        }

        return $this;
    }

    public function removeProduit(Produit $produit): static
    {
        if ($this->produits->removeElement($produit)) {
            if ($produit->getCategorie() === $this) {
                $produit->setCategorie(null);
            }
        }

        return $this;
    }
    
    public function __toString(): string
    {
        return $this->nom ?? 'Categorie';
    }
}