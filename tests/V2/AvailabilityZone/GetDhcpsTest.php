<?php

namespace Tests\V2\AvailabilityZone;

use App\Models\V2\Dhcp;
use Illuminate\Database\Eloquent\Model;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class GetDhcpsTest extends TestCase
{
    use DatabaseMigrations;

    protected Dhcp $dhcp;

    public function setUp(): void
    {
        parent::setUp();

        Model::withoutEvents(function () {
            $this->dhcp = factory(Dhcp::class)->create([
                'id' => 'dhcp-test',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id
            ]);
        });
    }

    public function testGetCollection()
    {
        $this->get(
            '/v2/availability-zones/' . $this->availabilityZone()->id.'/dhcps',
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
