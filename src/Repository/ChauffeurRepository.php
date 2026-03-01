<?php

namespace App\Repository;

use App\Entity\Chauffeur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Chauffeur>
 */
class ChauffeurRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Chauffeur::class);
    }

    public function search(?string $q): array
    {
        $qb = $this->createQueryBuilder('c');

        if ($q) {
            $qb->andWhere('
            c.firstName LIKE :q 
            OR c.lastName LIKE :q 
            OR CONCAT(c.firstName, \' \', c.lastName) LIKE :q
        ')
                ->setParameter('q', '%' . trim($q) . '%');
        }

        $qb->orderBy('c.lastName', 'ASC');

        return $qb->getQuery()->getResult();
    }
}
