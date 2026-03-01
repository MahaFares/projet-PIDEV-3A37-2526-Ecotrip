<?php

namespace App\Repository;

use App\Entity\PaymentReservation;
use App\Enum\PaymentStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PaymentReservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PaymentReservation::class);
    }

    public function findByStatus(PaymentStatus $status): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.paymentStatus = :status')
            ->setParameter('status', $status)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findCompletedPayments(): array
    {
        return $this->findByStatus(PaymentStatus::COMPLETED);
    }

    public function findPendingPayments(): array
    {
        return $this->findByStatus(PaymentStatus::PENDING);
    }

    public function save(PaymentReservation $payment): void
    {
        $this->getEntityManager()->persist($payment);
        $this->getEntityManager()->flush();
    }

    public function remove(PaymentReservation $payment): void
    {
        $this->getEntityManager()->remove($payment);
        $this->getEntityManager()->flush();
    }
}
