<?php

namespace Tests\V2\Dhcp;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Dhcp;
use App\Models\V2\Region;
use App\Models\V2\Vpc;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class GetTest extends TestCase
{
    use DatabaseMigrations;

    /** @var Dhcp */
    private $dhcp;

    public function setUp(): void
    {
        parent::setUp();
        $this->dhcp = factory(Dhcp::class)->create([
            'vpc_id' => $this->vpc()->getKey(),
            'availability_zone_id' => $this->availabilityZone()->getKey(),
        ]);
    }

    public function testNoPermsIsDenied()
    {
        $this->get('/v2/dhcps')->seeJson([
            'title' => 'Unauthorized',
            'detail' => 'Unauthorized',
            'status' => 401,
        ])->assertResponseStatus(401);
    }

    public function testGetCollection()
    {
        $this->get('/v2/dhcps', [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.read',
        ])->seeJson([
            'id' => $this->dhcp->id,
            'vpc_id' => $this->dhcp->vpc_id,
        ])->assertResponseStatus(200);
    }

    public function testGetItemDetail()
    {
        $this->get('/v2/dhcps/' . $this->dhcp->getKey(), [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.read',
        ])->seeJson([
            'id' => $this->dhcp->id,
            'vpc_id' => $this->dhcp->vpc_id,
        ])->assertResponseStatus(200);
    }
}
