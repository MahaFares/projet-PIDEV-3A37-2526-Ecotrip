<?php

namespace App\Repository;

use App\Entity\ActivitySchedule;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ActivitySchedule>
 */
class ActivityScheduleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ActivitySchedule::class);
    }

    //    /**
    //     * @return ActivitySchedule[] Returns an array of ActivitySchedule objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('a.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?ActivitySchedule
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    /**
     * Find upcoming schedules (starting from now)
     *
     * @return ActivitySchedule[]
     */
    public function findUpcomingSchedules(int $limit = 5): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.startAt > :now')
            ->setParameter('now', new \DateTime())
            ->orderBy('s.startAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
