<?php

namespace App\Repository;

use App\Entity\Hebergement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Hebergement>
 */
class HebergementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Hebergement::class);
    }

    /**
     * Find all hebergements with categorie, chambres and equipements eager-loaded (avoids N+1).
     *
     * @return Hebergement[]
     */
    public function findAllForListing(): array
    {
        return $this->createQueryBuilder('h')
            ->leftJoin('h.categorie', 'c')->addSelect('c')
            ->leftJoin('h.chambres', 'ch')->addSelect('ch')
            ->leftJoin('h.equipements', 'e')->addSelect('e')
            ->orderBy('h.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find one hebergement by id with chambres loaded (for add-to-cart).
     */
    public function findWithChambres(int $id): ?Hebergement
    {
        return $this->createQueryBuilder('h')
            ->leftJoin('h.chambres', 'ch')->addSelect('ch')
            ->where('h.id = :id')->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Get a QueryBuilder for hebergements by filters.
     */
    public function getQueryBuilderByFilters(?string $q = null, ?int $minStars = null, ?int $maxStars = null, ?bool $active = null)
    {
        $qb = $this->createQueryBuilder('h')
            ->orderBy('h.nom', 'ASC');

        if ($q) {
            $qb->andWhere('h.nom LIKE :q OR h.adresse LIKE :q OR h.ville LIKE :q')
                ->setParameter('q', '%' . $q . '%');
        }

        if ($minStars !== null) {
            $qb->andWhere('h.nbEtoiles >= :minStars')->setParameter('minStars', $minStars);
        }

        if ($maxStars !== null) {
            $qb->andWhere('h.nbEtoiles <= :maxStars')->setParameter('maxStars', $maxStars);
        }

        if ($active !== null) {
            $qb->andWhere('h.actif = :active')->setParameter('active', $active);
        }

        return $qb;
    }

    /**
     * Find hebergements by filters: text search (name/address/city), star rating and active flag.
     *
     * @return Hebergement[]
     */
    public function findByFilters(?string $q = null, ?int $minStars = null, ?int $maxStars = null, ?bool $active = null): array
    {
        return $this->getQueryBuilderByFilters($q, $minStars, $maxStars, $active)->getQuery()->getResult();
    }

    /**
     * Count hebergements per category (for pie chart).
     *
     * @return array<string, int> [ 'Category Name' => count, ... ]
     */
    public function getCountByCategory(): array
    {
        $rows = $this->createQueryBuilder('h')
            ->select('c.nom AS categoryName', 'COUNT(h.id) AS cnt')
            ->leftJoin('h.categorie', 'c')
            ->groupBy('c.id', 'c.nom')
            ->orderBy('cnt', 'DESC')
            ->getQuery()
            ->getResult();

        $out = [];
        foreach ($rows as $row) {
            $name = $row['categoryName'] ?? 'Sans catégorie';
            $out[$name] = (int) $row['cnt'];
        }
        return $out;
    }

    /**
     * Top N hebergements by number of chambres (for line/bar chart).
     *
     * @return array<array{nom: string, count: int}>
     */
    public function getChambresCountPerHebergement(int $limit = 10): array
    {
        $rows = $this->createQueryBuilder('h')
            ->select('h.nom', 'COUNT(ch.id) AS chambreCount')
            ->leftJoin('h.chambres', 'ch')
            ->groupBy('h.id', 'h.nom')
            ->orderBy('chambreCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return array_map(fn ($r) => [
            'nom' => $r['nom'],
            'count' => (int) $r['chambreCount'],
        ], $rows);
    }

//    /**
//     * @return Hebergement[] Returns an array of Hebergement objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('h')
//            ->andWhere('h.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('h.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Hebergement
//    {
//        return $this->createQueryBuilder('h')
//            ->andWhere('h.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
