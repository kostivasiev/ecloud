<?php

namespace Tests\V2\Instances;

use GuzzleHttp\Psr7\Response;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class PowerResetTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->kingpinServiceMock()->expects('get')
            ->withArgs(['/api/v2/vpc/' . $this->instance()->vpc_id . '/instance/' . $this->instance()->id])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'powerState' => 'poweredOff',
                    'toolsRunningStatus' => 'guestToolsRunning',
                ]));
            });

        $this->kingpinServiceMock()->expects('get')
            ->withArgs(['/api/v2/vpc/' . $this->instance()->vpc_id . '/instance/' . $this->instance()->id])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'powerState' => 'poweredOn',
                    'toolsRunningStatus' => 'guestToolsRunning',
                ]));
            });
        $this->kingpinServiceMock()
            ->shouldReceive('put')
            ->withArgs(['/api/v2/vpc/' . $this->vpc()->id . '/instance/' . $this->instance()->id . '/power/reset'])
            ->andReturn(
                new Response(200)
            );
    }

    public function testPowerReset()
    {
        $this->put(
            '/v2/instances/' . $this->instance()->id.'/power-reset',
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups'    => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(202);
    }
}
