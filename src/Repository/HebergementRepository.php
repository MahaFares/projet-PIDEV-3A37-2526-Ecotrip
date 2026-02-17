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
     * Find hebergements by filters: text search (name/address/city), star rating and active flag.
     *
     * @return Hebergement[]
     */
    public function findByFilters(?string $q = null, ?int $minStars = null, ?int $maxStars = null, ?bool $active = null): array
    {
        $qb = $this->createQueryBuilder('h')
            ->orderBy('h.nom', 'ASC');

        if ($q) {
            $qb->andWhere('h.nom LIKE :q OR h.adresse LIKE :q OR h.ville LIKE :q')
               ->setParameter('q', '%'.$q.'%');
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

        return $qb->getQuery()->getResult();
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
