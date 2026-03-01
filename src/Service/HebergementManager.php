<?php

namespace App\Service;

use App\Entity\Hebergement;

class HebergementManager
{
    public function validate(Hebergement $hebergement): bool
    {
        if (empty($hebergement->getNom())) {
            throw new \InvalidArgumentException('Nom obligatoire');
        }

        if ($hebergement->getNbEtoiles() === null) {
            throw new \InvalidArgumentException('Nombre d\'étoiles obligatoire');
        }

        $etoiles = $hebergement->getNbEtoiles();
        if ($etoiles < 0 || $etoiles > 5) {
            throw new \InvalidArgumentException('Nombre d\'étoiles invalide');
        }

        $description = $hebergement->getDescription();
        if ($description === null || trim($description) === '') {
            throw new \InvalidArgumentException('La description de l\'hébergement est requise');
        }
        if (mb_strlen(trim($description)) < 100) {
            throw new \InvalidArgumentException('La description doit contenir au moins 10 caractères');
        }

        return true;
    }
}