<!-- <?php

namespace App\Tests\Service;

use App\Entity\Transport;
use App\Service\TransportManager;
use PHPUnit\Framework\TestCase;

class TransportManagerTest extends TestCase
{
    public function testValidTransport()
    {
        $t = new Transport();
        $t->setType('Bus');
        $t->setCapacite(50);
        $t->setEmissionco2(10.5);

        $this->assertTrue((new TransportManager())->validate($t));
    }

    public function testInvalidType()
    {
        $this->expectException(\InvalidArgumentException::class);

        $t = new Transport();
        $t->setType('');
        $t->setCapacite(50);
        $t->setEmissionco2(0);

        (new TransportManager())->validate($t);
    }

    public function testInvalidCapaciteTooLow()
    {
        $this->expectException(\InvalidArgumentException::class);

        $t = new Transport();
        $t->setType('Bus');
        $t->setCapacite(0);
        $t->setEmissionco2(0);

        (new TransportManager())->validate($t);
    }

    public function testInvalidCapaciteTooHigh()
    {
        $this->expectException(\InvalidArgumentException::class);

        $t = new Transport();
        $t->setType('Bus');
        $t->setCapacite(600);
        $t->setEmissionco2(0);

        (new TransportManager())->validate($t);
    }

    public function testInvalidEmissionCo2Negative()
    {
        $this->expectException(\InvalidArgumentException::class);

        $t = new Transport();
        $t->setType('Bus');
        $t->setCapacite(50);
        $t->setEmissionco2(-5.0);

        (new TransportManager())->validate($t);
    }
} //-->