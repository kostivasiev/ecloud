<?php

namespace Tests\V2\Instances;

use App\Models\V2\Instance;
use GuzzleHttp\Psr7\Response;
use Tests\TestCase;

class GetTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->instanceModel();

        $this->kingpinServiceMock()->shouldReceive('get')->andReturn(
            new Response(200, [], json_encode([
                'powerState' => 'poweredOn',
                'toolsRunningStatus' => 'guestToolsRunning'
            ]))
        );
    }

    public function testGetCollection()
    {
        $this->get(
            '/v2/instances',
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )->assertJsonFragment([
            'id' => $this->instanceModel()->id,
            'name' => $this->instanceModel()->name,
            'vpc_id' => $this->instanceModel()->vpc_id,
            'platform' => 'Linux',
        ])->assertStatus(200);
    }

    public function testCantSeeHiddenResource()
    {
        $hidden = Instance::factory()->create([
            'is_hidden' => true,
            'vpc_id' => $this->vpc()->id,
        ]);

        $this->get(
            '/v2/instances/' . $hidden->id,
            [
                'X-consumer-custom-id' => '1-1',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )->assertJsonFragment([
            'title' => 'Not found',
            'detail' => 'No Instance with that ID was found',
        ])->assertStatus(404);
    }

    public function testGetResource()
    {
        $get = $this->get(
            '/v2/instances/' . $this->instanceModel()->id,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )->assertJsonFragment([
            'id' => $this->instanceModel()->id,
            'name' => $this->instanceModel()->name,
            'vpc_id' => $this->instanceModel()->vpc_id,
            'image_id' => $this->image()->id,
        ])->assertJsonFragment([
            'platform' => 'Linux',
        ])->assertStatus(200);
    }

    public function testGetFloatingIps()
    {
        $this->floatingIp()->resource()->associate($this->nic())->save();

        $this->get(
            '/v2/instances/' . $this->instanceModel()->id . '/floating-ips',
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->assertJsonFragment([
                'id' => 'fip-test',
            ])
            ->assertStatus(200);
    }
}
