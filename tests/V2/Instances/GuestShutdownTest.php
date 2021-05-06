<?php

namespace Tests\V2\Instances;

use GuzzleHttp\Psr7\Response;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class GuestShutdownTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->kingpinServiceMock()
            ->shouldReceive('put')
            ->withArgs(['/api/v2/vpc/' . $this->vpc()->id . '/instance/' . $this->instance()->id . '/power/guest/shutdown'])
            ->andReturn(
                new Response(200)
        );
    }

    public function testShutdown()
    {
        $this->put(
            '/v2/instances/' . $this->instance()->id . '/power-shutdown',
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(202);
    }
}
