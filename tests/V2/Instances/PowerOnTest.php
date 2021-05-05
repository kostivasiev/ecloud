<?php

namespace Tests\V2\Instances;

use GuzzleHttp\Psr7\Response;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class PowerOnTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->kingpinServiceMock()->shouldReceive('post')->withArgs(['/api/v2/vpc/' . $this->vpc()->id . '/instance/' . $this->instance()->id . '/power'])->andReturn(
            new Response(200)
        );
    }

    public function testPowerOn()
    {
        $this->put(
            '/v2/instances/' . $this->instance()->id . '/power-on',
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(202);
    }
}
