<?php

namespace App\Repository;

use App\Entity\Reservation;
use App\Enum\ReservationType;
use App\Enum\ReservationStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ReservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reservation::class);
    }

    /**
     * Find reservations by user
     */
    public function findByUser(int $userId): array
    {
        return $this->createQueryBuilder('r')
            ->innerJoin('r.user', 'u')
            ->where('u.id = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find reservations by status
     */
    public function findByStatus(ReservationStatus $status): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.status = :status')
            ->setParameter('status', $status)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find reservations by type
     */
    public function findByType(ReservationType $type): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.reservationType = :type')
            ->setParameter('type', $type)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find user's reservations by type
     */
    public function findUserReservationsByType(int $userId, ReservationType $type): array
    {
        return $this->createQueryBuilder('r')
            ->innerJoin('r.user', 'u')
            ->where('u.id = :userId')
            ->andWhere('r.reservationType = :type')
            ->setParameter('userId', $userId)
            ->setParameter('type', $type)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find pending reservations
     */
    public function findPendingReservations(): array
    {
        return $this->findByStatus(ReservationStatus::PENDING);
    }

    /**
     * Find confirmed reservations
     */
    public function findConfirmedReservations(): array
    {
        return $this->findByStatus(ReservationStatus::CONFIRMED);
    }

    /**
     * Count reservations by status
     */
    public function countByStatus(ReservationStatus $status): int
    {
        return $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.status = :status')
            ->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Get total revenue
     */
    public function getTotalRevenue(): float
    {
        $result = $this->createQueryBuilder('r')
            ->select('SUM(r.totalPrice)')
            ->where('r.status = :status')
            ->setParameter('status', ReservationStatus::CONFIRMED)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ?? 0;
    }

    /**
     * Find reservations with optional filters (for admin list).
     *
     * @param ReservationStatus|null $status
     * @param ReservationType|null $type
     * @param string|null $userSearch  User email or username (LIKE)
     * @param \DateTimeInterface|null $dateFrom  Filter by dateFrom >=
     * @param \DateTimeInterface|null $dateTo    Filter by dateTo <= or dateFrom <=
     */
    public function findWithFilters(
        ?ReservationStatus $status = null,
        ?ReservationType $type = null,
        ?string $userSearch = null,
        ?\DateTimeInterface $dateFrom = null,
        ?\DateTimeInterface $dateTo = null
    ): array {
        $qb = $this->createQueryBuilder('r')
            ->innerJoin('r.user', 'u')
            ->addOrderBy('r.createdAt', 'DESC');

        if ($status !== null) {
            $qb->andWhere('r.status = :status')->setParameter('status', $status);
        }
        if ($type !== null) {
            $qb->andWhere('r.reservationType = :type')->setParameter('type', $type);
        }
        if ($userSearch !== null && $userSearch !== '') {
            $qb->andWhere('u.email LIKE :search OR u.username LIKE :search')
                ->setParameter('search', '%' . $userSearch . '%');
        }
        if ($dateFrom !== null) {
            $qb->andWhere('r.dateFrom >= :dateFrom')->setParameter('dateFrom', $dateFrom);
        }
        if ($dateTo !== null) {
            $qb->andWhere('(r.dateTo <= :dateTo OR (r.dateTo IS NULL AND r.dateFrom <= :dateTo))')
                ->setParameter('dateTo', $dateTo);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Stats for admin dashboard: total count, by status, total revenue (confirmed).
     */
    public function getStatsForAdmin(): array
    {
        $total = (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $pending = $this->countByStatus(ReservationStatus::PENDING);
        $confirmed = $this->countByStatus(ReservationStatus::CONFIRMED);
        $cancelled = $this->countByStatus(ReservationStatus::CANCELLED);
        $revenue = $this->getTotalRevenue();

        return [
            'total' => $total,
            'pending' => $pending,
            'confirmed' => $confirmed,
            'cancelled' => $cancelled,
            'revenue' => $revenue,
        ];
    }

    public function save(Reservation $reservation): void
    {
        $this->getEntityManager()->persist($reservation);
        $this->getEntityManager()->flush();
    }

    public function remove(Reservation $reservation): void
    {
        $this->getEntityManager()->remove($reservation);
        $this->getEntityManager()->flush();
    }
}
