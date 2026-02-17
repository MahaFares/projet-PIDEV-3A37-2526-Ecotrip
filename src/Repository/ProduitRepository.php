<?php

namespace App\Repository;

use App\Entity\Produit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Produit>
 */
class ProduitRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Produit::class);
    }

    /**
     * Find produits by filters: text search (name), price range and availability (stock > 0).
     *
     * @return Produit[]
     */
    public function findByFilters(?string $q = null, ?float $minPrice = null, ?float $maxPrice = null, ?bool $available = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.categorie', 'c')
            ->addSelect('c')
            ->orderBy('p.nom', 'ASC');

        if ($q) {
            $qb->andWhere('p.nom LIKE :q')->setParameter('q', '%'.$q.'%');
        }

        if ($minPrice !== null) {
            $qb->andWhere('p.prix >= :minPrice')->setParameter('minPrice', $minPrice);
        }

        if ($maxPrice !== null) {
            $qb->andWhere('p.prix <= :maxPrice')->setParameter('maxPrice', $maxPrice);
        }

        if ($available !== null) {
            if ($available) {
                $qb->andWhere('p.stock > 0');
            } else {
                $qb->andWhere('p.stock = 0');
            }
        }

        return $qb->getQuery()->getResult();
    }
    public function countByStockGreaterThanZero(): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.idProduit)')
            ->where('p.stock > 0')
            ->getQuery()
            ->getSingleScalarResult() ?? 0;
    }

    public function countByStockZero(): int
    {
        return $this->count(['stock' => 0]);
    }
}
