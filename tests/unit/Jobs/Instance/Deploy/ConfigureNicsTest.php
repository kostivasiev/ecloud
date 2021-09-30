<?php

namespace Tests\unit\Jobs\Instance\Deploy;

use App\Jobs\Instance\Deploy\ConfigureNics;
use GuzzleHttp\Psr7\Response;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ConfigureNicsTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function testAssignsNic()
    {
        $this->kingpinServiceMock()->expects('get')
            ->withArgs(['/api/v2/vpc/vpc-test/instance/i-test'])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'nics' => [
                        [
                            'macAddress' => 'AA:BB:CC:DD:EE:FF'
                        ]
                    ]
                ]));
            });

        dispatch(new ConfigureNics($this->instance()));

        Event::assertNotDispatched(JobFailed::class);

        $this->assertEquals(1, $this->instance()->nics()->count());
    }
}
