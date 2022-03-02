<?php

namespace Tests\V2\Instances;

use App\Services\V2\KingpinService;
use GuzzleHttp\Psr7\Response;
use Tests\TestCase;

class GuestShutdownTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->kingpinServiceMock()
            ->allows('put')
            ->withArgs(['/api/v2/vpc/' . $this->vpc()->id . '/instance/' . $this->instanceModel()->id . '/power/guest/shutdown'])
            ->andReturns(
                new Response(200)
            );

        $this->kingpinServiceMock()->allows('get')
            ->andReturn(
                new Response(200, [], json_encode([
                    'powerState' => KingpinService::INSTANCE_POWERSTATE_POWEREDOFF,
                    'toolsRunningStatus' => KingpinService::INSTANCE_TOOLSRUNNINGSTATUS_RUNNING,
                ]))
            );
    }

    public function testShutdown()
    {
        $this->put(
            '/v2/instances/' . $this->instanceModel()->id . '/power-shutdown',
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(202);
    }
}
