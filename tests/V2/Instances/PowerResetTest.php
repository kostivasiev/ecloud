<?php

namespace Tests\V2\Instances;

use GuzzleHttp\Psr7\Response;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class PowerResetTest extends TestCase
{
    use DatabaseMigrations;

    public function setUp(): void
    {
        parent::setUp();

        $this->kingpinServiceMock()->shouldReceive('put')->withArgs(['/api/v2/vpc/' . $this->vpc()->getKey() . '/instance/' . $this->instance()->getKey() . '/power/reset'])->andReturn(
            new Response(200)
        );
    }

    public function testPowerReset()
    {
        $this->put(
            '/v2/instances/' . $this->instance()->getKey().'/power-reset',
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups'    => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(202);
    }
}
