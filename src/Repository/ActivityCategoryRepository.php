<?php

namespace App\Repository;

use App\Entity\ActivityCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ActivityCategory>
 */
class ActivityCategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ActivityCategory::class);
    }

    //    /**
    //     * @return ActivityCategory[] Returns an array of ActivityCategory objects
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

    //    public function findOneBySomeField($value): ?ActivityCategory
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    /**
     * Get the count of activities for each category
     *
     * @return array Array of arrays with category objects and activity counts
     */
    public function getActivitiesCountByCategory(): array
    {
        $results = $this->createQueryBuilder('c')
            ->select('c, COUNT(a.id) as count')
            ->leftJoin('c.activities', 'a')
            ->groupBy('c.id')
            ->orderBy('count', 'DESC')
            ->getQuery()
            ->getResult();

        $data = [];
        foreach ($results as $result) {
            $data[] = [
                'category' => $result[0],
                'count' => (int)$result['count'],
            ];
        }
        return $data;
    }
}
