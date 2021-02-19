<?php

namespace Tests\V2\Instances;

use GuzzleHttp\Psr7\Response;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class PowerOnTest extends TestCase
{
    use DatabaseMigrations;

    public function setUp(): void
    {
        parent::setUp();

        $this->kingpinServiceMock()->shouldReceive('post')->withArgs(['/api/v2/vpc/' . $this->vpc()->getKey() . '/instance/' . $this->instance()->getKey() . '/power'])->andReturn(
            new Response(200)
        );
    }

    public function testPowerOn()
    {
        $this->put(
            '/v2/instances/' . $this->instance()->getKey() . '/power-on',
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(202);
    }
}
