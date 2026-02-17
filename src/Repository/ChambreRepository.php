<?php

namespace App\Repository;

use App\Entity\Chambre;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Chambre>
 */
class ChambreRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Chambre::class);
    }

    public function findBySearchQuery(string $query): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.hebergement', 'h')
            ->where('c.numero LIKE :query')
            ->orWhere('c.type LIKE :query')
            ->orWhere('h.nom LIKE :query')
            ->orWhere('h.ville LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('c.numero', 'ASC')
            ->getQuery()
            ->getResult();
    }
    /**
     * @return Chambre[]
     */
    public function findByFilters(?string $q = null): array
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.hebergement', 'h')
            ->orderBy('c.numero', 'ASC');

        if ($q) {
            $qb->andWhere('c.numero LIKE :q OR c.type LIKE :q OR h.nom LIKE :q')
                ->setParameter('q', '%' . $q . '%');
        }

        return $qb->getQuery()->getResult();
    }
}
