<!-- <?php

namespace App\Tests\Service;

use App\Entity\Produit;
use App\Service\ProductManager;
use PHPUnit\Framework\TestCase;

class ProductManagerTest extends TestCase
{
    private function validProduct(): Produit
    {
        $p = new Produit();
        $p->setNom('Sac randonnée');
        $p->setPrix('10.00');
        $p->setStock(5);

        return $p;
    }

    public function testValidProduct()
    {
        $p = $this->validProduct();

        $this->assertTrue((new ProductManager())->validate($p));
    }

    public function testInvalidNom()
    {
        $this->expectException(\InvalidArgumentException::class);

        $p = $this->validProduct();
        $p->setNom('');

        (new ProductManager())->validate($p);
    }

    public function testInvalidNegativePrice()
    {
        $this->expectException(\InvalidArgumentException::class);

        $p = $this->validProduct();
        $p->setPrix('-1.00');

        (new ProductManager())->validate($p);
    }

    public function testInvalidNegativeStock()
    {
        $this->expectException(\InvalidArgumentException::class);

        $p = $this->validProduct();
        $p->setStock(-5);

        (new ProductManager())->validate($p);
    }

    public function testInvalidNomOnlyDigits()
    {
        $this->expectException(\InvalidArgumentException::class);

        $p = $this->validProduct();
        $p->setNom('12345');

        (new ProductManager())->validate($p);
    }
} //-->