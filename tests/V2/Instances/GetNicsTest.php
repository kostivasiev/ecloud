<?php

namespace Tests\V2\Instances;

use Faker\Factory as Faker;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\DatabaseMigrations;;
use Tests\TestCase;

class GetNicsTest extends TestCase
{
    protected \Faker\Generator $faker;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();

        $this->kingpinServiceMock()->shouldReceive('get')->andReturn(
            new Response(200, [], json_encode(['powerState' => 'poweredOn']))
        );
    }

    public function testGetCollection()
    {
        $this->nic();

        $this->get(
            '/v2/instances/' . $this->instanceModel()->id . '/nics',
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups'    => 'ecloud.read',
            ]
        )
            ->assertJsonFragment([
                'id'          => $this->nic()->id,
                'mac_address' => $this->nic()->mac_address,
                'instance_id' => $this->nic()->instance_id,
                'network_id'  => $this->nic()->network_id,
            ])
            ->assertStatus(200);
    }
}
