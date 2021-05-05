<?php

namespace Tests\V2\Instances;

use GuzzleHttp\Psr7\Response;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class GetTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->instance();

        $this->kingpinServiceMock()->shouldReceive('get')->andReturn(
            new Response(200, [], json_encode([
                'powerState' => 'poweredOn',
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
                'id' => $this->instance()->id,
                'name' => $this->instance()->name,
                'vpc_id' => $this->instance()->vpc_id,
                'platform' => 'Linux',
            ])
            ->assertResponseStatus(200);
    }

    public function testGetResource()
    {
        $this->get(
            '/v2/instances/' . $this->instance()->id,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id' => $this->instance()->id,
                'name' => $this->instance()->name,
                'vpc_id' => $this->instance()->vpc_id,
                'image_id' => $this->image()->id,
            ])
            ->assertResponseStatus(200);

        $result = json_decode($this->response->getContent());

        // Test to ensure that platform attribute is present
        $this->seeJson([
            'platform' => 'Linux',
        ]);
    }
}
