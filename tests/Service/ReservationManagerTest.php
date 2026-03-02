<?php

namespace App\Tests\Service;

use App\Entity\Reservation;
use App\Entity\Activity;
use App\Enum\ReservationType;
use App\Enum\ReservationStatus;
use App\Service\ReservationManager;
use PHPUnit\Framework\TestCase;

class ReservationManagerTest extends TestCase
{
    private function createValidReservation(): Reservation
    {
        $activity = new Activity();
        $activity->setMaxParticipants(10);
        
        $reservation = new Reservation();
        $reservation->setNumberOfPersons(2);
        $reservation->setDateFrom(
            new \DateTime('+1 day')
        );
        $reservation->setTotalPrice(100);
        $reservation->setActivity($activity);
        $reservation->setStatus(ReservationStatus::PENDING);

        return $reservation;
    }

    public function testValidReservation()
    {
        $manager = new ReservationManager();
        $reservation = $this->createValidReservation();

        $this->assertTrue($manager->validate($reservation));
    }

    public function testParticipantsCannotBeZero()
    {
        $this->expectException(\InvalidArgumentException::class);

        $reservation = $this->createValidReservation();
        $reservation->setNumberOfPersons(0);

        (new ReservationManager())->validate($reservation);
    }

    public function testReservationDateCannotBePast()
    {
        $this->expectException(\InvalidArgumentException::class);

        $reservation = $this->createValidReservation();
        $reservation->setDateFrom(
            new \DateTime('-1 day')
        );

        (new ReservationManager())->validate($reservation);
    }

    public function testTotalPriceMustBePositive()
    {
        $this->expectException(\InvalidArgumentException::class);

        $reservation = $this->createValidReservation();
        $reservation->setTotalPrice(-100);

        (new ReservationManager())->validate($reservation);
    }

    public function testCapacityExceeded()
    {
        $this->expectException(\InvalidArgumentException::class);

        $reservation = $this->createValidReservation();
        $reservation->setNumberOfPersons(20);

        (new ReservationManager())->validate($reservation);
    }
}