<?php

namespace Tests\V2\AvailabilityZone;

use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\DatabaseMigrations;;
use Tests\TestCase;

class GetInstancesTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->instanceModel();
        $this->kingpinServiceMock()->shouldReceive('get')->andReturn(
            new Response(200, [], json_encode(['powerState' => 'poweredOn']))
        );
    }

    public function testGetCollection()
    {
        $this->get(
            '/v2/availability-zones/'.$this->availabilityZone()->id.'/instances',
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups'    => 'ecloud.read',
            ]
        )
            ->assertJsonFragment([
                'id' => $this->instanceModel()->id,
                'name' => $this->instanceModel()->name,
                'vpc_id' => $this->instanceModel()->vpc_id,
                'platform' => 'Linux',
            ])
            ->assertStatus(200);
    }
}
