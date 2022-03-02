<?php

namespace Tests\V2\AvailabilityZone;

use App\Models\V2\Dhcp;
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;

class GetDhcpsTest extends TestCase
{
    protected Dhcp $dhcp;

    public function setUp(): void
    {
        parent::setUp();

        Model::withoutEvents(function () {
            $this->dhcp = Dhcp::factory()->create([
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
            ->assertJsonFragment([
                'id'       => $this->dhcp->id,
                'vpc_id'   => $this->dhcp->vpc_id,
            ])
            ->assertStatus(200);
    }
}
