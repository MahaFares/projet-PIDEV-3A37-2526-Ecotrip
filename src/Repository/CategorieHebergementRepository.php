<?php

namespace App\Repository;

use App\Entity\CategorieHebergement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CategorieHebergement>
 */
class CategorieHebergementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CategorieHebergement::class);
    }

    public function findBySearchQuery(string $query): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.nom LIKE :query OR c.description LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('c.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return CategorieHebergement[]
     */
    public function findByFilters(?string $q = null): array
    {
        $qb = $this->createQueryBuilder('c')
            ->orderBy('c.nom', 'ASC');

        if ($q) {
            $qb->andWhere('c.nom LIKE :q OR c.description LIKE :q')
                ->setParameter('q', '%' . $q . '%');
        }

        return $qb->getQuery()->getResult();
    }
}
