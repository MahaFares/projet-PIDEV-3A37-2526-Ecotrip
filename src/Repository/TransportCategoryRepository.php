<?php

namespace App\Repository;

use App\Entity\TransportCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TransportCategory>
 */
class TransportCategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TransportCategory::class);
    }

    public function search(?string $q): array
    {
        $qb = $this->createQueryBuilder('c');
        if ($q) {
            $qb->andWhere('c.name LIKE :q')
                ->setParameter('q', '%' . $q . '%');
        }
        $qb->orderBy('c.name', 'ASC');
        return $qb->getQuery()->getResult();
    }
}
