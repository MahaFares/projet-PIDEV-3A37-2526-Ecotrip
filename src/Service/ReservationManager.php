<?php

namespace App\Service;

use App\Entity\Reservation;

class ReservationManager
{
    public function validate(Reservation $reservation): bool
    {
        // Rule 1: participants > 0
        if ($reservation->getNumberOfPersons() <= 0) {
            throw new \InvalidArgumentException(
                'Le nombre de participants doit être supérieur à 0'
            );
        }

        // Rule 2: date not in past
        if ($reservation->getDateFrom() < new \DateTime()) {
            throw new \InvalidArgumentException(
                'La date de réservation ne peut pas être passée'
            );
        }

        // Rule 3: total price positive
        if ($reservation->getTotalPrice() <= 0) {
            throw new \InvalidArgumentException(
                'Le prix total doit être positif'
            );
        }

        // Rule 4: capacity check for activity
        if ($reservation->getNumberOfPersons() > $reservation->getActivity()->getMaxParticipants()) {
            throw new \InvalidArgumentException('Capacité dépassée');
        }

        return true;
    }
}

