<?php

namespace Tests\V2\Instances;

use App\Services\V2\KingpinService;
use GuzzleHttp\Psr7\Response;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class PowerResetTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->kingpinServiceMock()->shouldReceive('put')->withArgs(['/api/v2/vpc/' . $this->vpc()->id . '/instance/' . $this->instanceModel()->id . '/power/reset'])->andReturn(
            new Response(200)
        );
    }

    public function testPowerReset()
    {
        $this->kingpinServiceMock()->allows('get')
            ->andReturn(
                new Response(200, [], json_encode([
                    'powerState' => KingpinService::INSTANCE_POWERSTATE_POWEREDOFF,
                    'toolsRunningStatus' => KingpinService::INSTANCE_TOOLSRUNNINGSTATUS_RUNNING,
                ]))
            );
        $this->put(
            '/v2/instances/' . $this->instanceModel()->id.'/power-reset',
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups'    => 'ecloud.write',
            ]
        )->assertStatus(202);
    }
}
