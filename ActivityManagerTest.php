<!-- <?php

namespace App\Tests\Service;

use App\Entity\Activity;
use App\Service\ActivityManager;
use PHPUnit\Framework\TestCase;

class ActivityManagerTest extends TestCase
{
    private function validActivity(): Activity
    {
        $a = new Activity();
        $a->setTitle('Randonnée');
        $a->setPrice(50);
        $a->setMaxParticipants(10);
        $a->setDurationMinutes(60);
        return $a;
    }

    public function testValidActivity()
    {
        $this->assertTrue(
            (new ActivityManager())->validate($this->validActivity())
        );
    }

    public function testInvalidPrice()
    {
        $this->expectException(\InvalidArgumentException::class);

        $a = $this->validActivity();
        $a->setPrice(0);

        (new ActivityManager())->validate($a);
    }

    public function testEmptyTitle()
    {
        $this->expectException(\InvalidArgumentException::class);

        $a = $this->validActivity();
        $a->setTitle('');

        (new ActivityManager())->validate($a);
    }

    public function testInvalidMaxParticipants()
    {
        $this->expectException(\InvalidArgumentException::class);

        $a = $this->validActivity();
        $a->setMaxParticipants(0);

        (new ActivityManager())->validate($a);
    }

    public function testInvalidDurationMinutes()
    {
        $this->expectException(\InvalidArgumentException::class);

        $a = $this->validActivity();
        $a->setDurationMinutes(0);

        (new ActivityManager())->validate($a);
    }

} //-->