<?php

namespace App\Service;

use App\Entity\Activity;

class ActivityManager
{
    public function validate(Activity $activity): bool
    {
        if (empty($activity->getTitle())) {
            throw new \InvalidArgumentException('Titre obligatoire');
        }

        if ($activity->getPrice() <= 0) {
            throw new \InvalidArgumentException('Prix invalide');
        }
        if ($activity->getMaxParticipants() <= 0) {
            throw new \InvalidArgumentException('Nombre de participants invalide');
        }
        if ($activity->getDurationMinutes() <= 0) {
            throw new \InvalidArgumentException('Durée invalide');
        }

        return true;
    }
}