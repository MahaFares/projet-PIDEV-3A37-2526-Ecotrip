<?php

namespace App\Service;

use App\Entity\Produit;

class ProductManager
{
    public function validate(Produit $product): bool
    {
        if (empty($product->getNom())) {
            throw new \InvalidArgumentException('Le nom du produit est requis');
        }

        if ($product->getPrix() === null || (float) $product->getPrix() < 0) {
            throw new \InvalidArgumentException('Le prix du produit doit être positif ou nul');
        }

        if ($product->getStock() === null || $product->getStock() < 0) {
            throw new \InvalidArgumentException('Le stock du produit doit être positif ou nul');
        }

        $nom = trim($product->getNom() ?? '');
        if ($nom !== '' && preg_match('/^\d+$/', $nom)) {
            throw new \InvalidArgumentException('Le nom du produit ne peut pas contenir uniquement des chiffres');
        }

        return true;
    }
}