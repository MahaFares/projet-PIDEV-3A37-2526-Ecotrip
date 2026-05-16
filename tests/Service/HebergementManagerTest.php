<?php

namespace App\Tests\Service;

use App\Entity\Hebergement;
use App\Service\HebergementManager;
use PHPUnit\Framework\TestCase;

class HebergementManagerTest extends TestCase
{
    private function validHebergement(): Hebergement
    {
        $h = new Hebergement();
        $h->setNom('Hotel Eco');
        $h->setNbEtoiles(3);
        $h->setDescription('Un hébergement confortable avec vue sur la montagne et petit-déjeuner inclus.');
        return $h;
    }

    public function testValidHebergement()
    {
        $this->assertTrue(
            (new HebergementManager())->validate($this->validHebergement())
        );
    }

    public function testInvalidNom()
    {
        $this->expectException(\InvalidArgumentException::class);

        $h = $this->validHebergement();
        $h->setNom('');

        (new HebergementManager())->validate($h);   
    }

    public function testInvalidNbEtoilesTooHigh()
    {
        $this->expectException(\InvalidArgumentException::class);

        $h = $this->validHebergement();
        $h->setNbEtoiles(6);

        (new HebergementManager())->validate($h);
    }

    public function testInvalidNbEtoilesTooLow()
    {
        $this->expectException(\InvalidArgumentException::class);

        $h = $this->validHebergement();
        $h->setNbEtoiles(-1);

        (new HebergementManager())->validate($h);
    }

    public function testInvalidDescriptionEmpty()
    {
        $this->expectException(\InvalidArgumentException::class);

        $h = $this->validHebergement();
        $h->setDescription('');

        (new HebergementManager())->validate($h);
    }

    public function testInvalidDescriptionTooShort()
    {
        $this->expectException(\InvalidArgumentException::class);

        $h = $this->validHebergement();
        $h->setDescription('Court');

        (new HebergementManager())->validate($h);
    }
}