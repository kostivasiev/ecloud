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
        )
            ->seeJson([
                'id' => $this->instanceModel()->id,
                'name' => $this->instanceModel()->name,
                'vpc_id' => $this->instanceModel()->vpc_id,
                'platform' => 'Linux',
            ])
            ->assertResponseStatus(200);
    }

    public function testCantSeeHiddenResource()
    {
        $hidden = factory(Instance::class)->create([
            'is_hidden' => true,
            'vpc_id' => $this->vpc()->id,
        ]);

        $this->get(
            '/v2/instances/' . $hidden->id,
            [
                'X-consumer-custom-id' => '1-1',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'title' => 'Not found',
                'detail' => 'No Instance with that ID was found',
            ])
            ->assertResponseStatus(404);
    }

    public function testGetResource()
    {
        $this->get(
            '/v2/instances/' . $this->instanceModel()->id,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id' => $this->instanceModel()->id,
                'name' => $this->instanceModel()->name,
                'vpc_id' => $this->instanceModel()->vpc_id,
                'image_id' => $this->image()->id,
            ])
            ->assertResponseStatus(200);

        $result = json_decode($this->response->getContent());

        // Test to ensure that platform attribute is present
        $this->seeJson([
            'platform' => 'Linux',
        ]);
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
            ->seeJson([
                'id' => 'fip-test',
            ])
            ->assertResponseStatus(200);
    }
}
