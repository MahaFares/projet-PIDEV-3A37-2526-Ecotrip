<?php

namespace App\Entity;

use App\Repository\ProduitRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: ProduitRepository::class)]
#[ORM\Table(name: 'produits')]
class Produit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_produit', type: 'integer')]
    private ?int $idProduit = null;

    #[ORM\Column(length: 150)]
    #[Assert\NotBlank(message: 'Le nom du produit est obligatoire.')]
    #[Assert\Length(max: 150, maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères.')]
    #[Assert\Type('string')]
    #[Assert\Callback([self::class, 'validateNom'])]
    private ?string $nom = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Assert\NotBlank(message: 'Le prix est obligatoire.')]
    #[Assert\Regex(pattern: '/^\d+(\.\d{1,2})?$/', message: 'Le prix doit être un nombre décimal valide (ex: 10 ou 10.50).')]
    #[Assert\GreaterThanOrEqual(value: 0, message: 'Le prix ne peut pas être négatif.')]
    private ?string $prix = null;

    #[ORM\Column(type: 'integer')]
    #[Assert\NotBlank(message: 'Le stock est obligatoire.')]
    #[Assert\Type('integer', message: 'Le stock doit être un entier.')]
    #[Assert\GreaterThanOrEqual(value: 0, message: 'Le stock ne peut pas être négatif.')]
    private ?int $stock = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    #[Assert\Type('string')]
    private ?string $image = null;

    #[ORM\ManyToOne(targetEntity: Categorie::class, inversedBy: 'produits')]
    #[ORM\JoinColumn(name: 'id_categorie', referencedColumnName: 'id_categorie', nullable: false)]
    #[Assert\NotBlank(message: 'La catégorie est obligatoire.')]
    private ?Categorie $categorie = null;

    /**
     * @var Collection<int, Commande>
     */
    #[ORM\OneToMany(targetEntity: Commande::class, mappedBy: 'produit')]
    private Collection $commandes;

    /**
     * @var Collection<int, LigneDeCommande>
     */
    #[ORM\OneToMany(targetEntity: LigneDeCommande::class, mappedBy: 'idProduct')]
    private Collection $ligneDeCommandes;

   

    public function __construct()
    {
        $this->commandes = new ArrayCollection();
        $this->ligneDeCommandes = new ArrayCollection();
    }

    public static function validateNom(mixed $object, ExecutionContextInterface $context): void
    {
        if ($object instanceof self) {
            $nom = $object->getNom();
            
            // Vérifier que le nom n'est pas vide
            if (empty(trim($nom ?? ''))) {
                $context->buildViolation('Le nom du produit ne peut pas être vide.')
                    ->atPath('nom')
                    ->addViolation();
            }
            
            // Vérifier que le nom ne contient pas que des chiffres
            if (!empty($nom) && preg_match('/^\d+$/', trim($nom))) {
                $context->buildViolation('Le nom du produit ne peut pas contenir uniquement des chiffres.')
                    ->atPath('nom')
                    ->addViolation();
            }
        }
    }

    public function getId(): ?int
    {
        return $this->idProduit;
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

    public function getPrix(): ?string
    {
        return $this->prix;
    }

    public function setPrix(string $prix): static
    {
        $this->prix = $prix;

        return $this;
    }

    public function getStock(): ?int
    {
        return $this->stock;
    }

    public function setStock(int $stock): static
    {
        $this->stock = $stock;

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

    public function getCategorie(): ?Categorie
    {
        return $this->categorie;
    }

    public function setCategorie(?Categorie $categorie): static
    {
        $this->categorie = $categorie;

        return $this;
    }

    /**
     * @return Collection<int, Commande>
     */
    public function getCommandes(): Collection
    {
        return $this->commandes;
    }

    public function addCommande(Commande $commande): static
    {
        if (!$this->commandes->contains($commande)) {
            $this->commandes->add($commande);
            $commande->setProduit($this);
        }

        return $this;
    }

    public function removeCommande(Commande $commande): static
    {
        if ($this->commandes->removeElement($commande)) {
            if ($commande->getProduit() === $this) {
                $commande->setProduit(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, LigneDeCommande>
     */
    public function getLigneDeCommandes(): Collection
    {
        return $this->ligneDeCommandes;
    }

    public function addLigneDeCommande(LigneDeCommande $ligneDeCommande): static
    {
        if (!$this->ligneDeCommandes->contains($ligneDeCommande)) {
            $this->ligneDeCommandes->add($ligneDeCommande);
            $ligneDeCommande->setIdProduct($this);
        }

        return $this;
    }

    public function removeLigneDeCommande(LigneDeCommande $ligneDeCommande): static
    {
        if ($this->ligneDeCommandes->removeElement($ligneDeCommande)) {
            // set the owning side to null (unless already changed)
            if ($ligneDeCommande->getIdProduct() === $this) {
                $ligneDeCommande->setIdProduct(null);
            }
        }

        return $this;
    }


}