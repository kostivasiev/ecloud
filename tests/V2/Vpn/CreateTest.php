<?php

namespace Tests\V2\Vpn;

use App\Models\V2\Vpn;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class CreateTest extends TestCase
{
    public function testNotUserOwnedRouterIdIsFailed()
    {
        $this->post(
            '/v2/vpns',
            [
                'router_id' => $this->router()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
            ],
            [
                'X-consumer-custom-id' => '2-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title' => 'Validation Error',
                'detail' => 'The specified router id was not found',
                'status' => 422,
                'source' => 'router_id'
            ])
            ->assertResponseStatus(422);
    }

    public function testValidDataSucceeds()
    {
        $this->post(
            '/v2/vpns',
            [
                'router_id' => $this->router()->id,
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ])
            ->assertResponseStatus(201);
        $vpnId = (json_decode($this->response->getContent()))->data->id;
        $vpnItem = Vpn::findOrFail($vpnId);
        $this->assertEquals($vpnItem->router_id, $this->router()->id);
    }
}
