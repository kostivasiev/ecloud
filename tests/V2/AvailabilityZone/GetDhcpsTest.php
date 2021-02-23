<?php

namespace Tests\V2\AvailabilityZone;

use App\Models\V2\Dhcp;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class GetDhcpsTest extends TestCase
{
    use DatabaseMigrations;

    protected Dhcp $dhcp;

    public function setUp(): void
    {
        parent::setUp();

        $this->dhcp = factory(Dhcp::class)->create([
            'vpc_id' => $this->vpc()->getKey(),
            'availability_zone_id' => $this->availabilityZone()->id
        ]);
    }

    public function testGetCollection()
    {
        $this->get(
            '/v2/availability-zones/' . $this->availabilityZone()->getKey().'/dhcps',
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups'    => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id'       => $this->dhcp->id,
                'vpc_id'   => $this->dhcp->vpc_id,
            ])
            ->assertResponseStatus(200);
    }
}
