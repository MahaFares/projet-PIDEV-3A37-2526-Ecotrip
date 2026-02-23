<?php

namespace App\Repository;

use App\Entity\Transport;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Transport>
 */
class TransportRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Transport::class);
    }



    //    /**
    //     * @return Transport[] Returns an array of Transport objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('t.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Transport
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    public function findByFilters(
        ?string $type = null,
        ?float $minPrice = null,
        ?float $maxPrice = null,
        ?int $minCapacity = null,
        ?bool $available = null,
        ?string $q = null
    ): array {
        $qb = $this->createQueryBuilder('t')
            ->leftJoin('t.category', 'cat')
            ->leftJoin('t.chauffeur', 'c');

        if ($type) {
            $qb->andWhere('t.type = :type')
                ->setParameter('type', $type);
        }

        if ($minPrice !== null) {
            $qb->andWhere('t.prixparpersonne >= :minPrice')
                ->setParameter('minPrice', $minPrice);
        }

        if ($maxPrice !== null) {
            $qb->andWhere('t.prixparpersonne <= :maxPrice')
                ->setParameter('maxPrice', $maxPrice);
        }

        if ($minCapacity !== null) {
            $qb->andWhere('t.capacite >= :minCapacity')
                ->setParameter('minCapacity', $minCapacity);
        }

        if ($available !== null) {
            $qb->andWhere('t.disponible = :available')
                ->setParameter('available', $available);
        }

        if ($q) {
            $qb->andWhere('
            t.type LIKE :q
            OR cat.name LIKE :q
            OR c.firstName LIKE :q
            OR c.lastName LIKE :q
            OR CONCAT(c.firstName, \' \', c.lastName) LIKE :q
        ')
                ->setParameter('q', '%' . trim($q) . '%');
        }

        $qb->orderBy('t.type', 'ASC');

        return $qb->getQuery()->getResult();
    }

}
