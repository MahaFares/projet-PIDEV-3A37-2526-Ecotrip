<?php

namespace App\Service;

use App\Entity\Transport;

class TransportManager
{
    public function validate(Transport $transport): bool
    {
        if (empty($transport->getType())) {
            throw new \InvalidArgumentException('Le type de transport est requis');
        }

        $capacite = $transport->getCapacite();
        if ($capacite === null) {
            throw new \InvalidArgumentException('La capacité du transport est requise');
        }

        if ($capacite < 1 || $capacite > 500) {
            throw new \InvalidArgumentException('La capacité du transport doit être comprise entre 1 et 500');
        }

        $emissionCo2 = $transport->getEmissionco2();
        if ($emissionCo2 === null) {
            throw new \InvalidArgumentException('L\'émission CO2 du transport est requise');
        }
        if ($emissionCo2 < 0) {
            throw new \InvalidArgumentException('L\'émission CO2 du transport ne peut pas être négative');
        }

        return true;
    }
}