<?php

namespace Jobs\VpnService\Nsx;

use App\Jobs\VpnService\Nsx\UndeployCheck;
use App\Models\V2\VpnService;
use GuzzleHttp\Psr7\Response;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use function dispatch;
use function factory;

class UndeployCheckTest extends TestCase
{
    public VpnService $vpnService;

    public function setUp(): void
    {
        parent::setUp();
        $this->vpnService = factory(VpnService::class)->create([
            'id' => 'vpn-' . uniqid(),
            'name' => 'Unit Test VPN',
            'router_id' => $this->router()->id,
        ]);
    }

    public function testNotFoundSucceeds()
    {
        $this->nsxServiceMock()->expects('get')
            ->withArgs([
                'policy/api/v1/infra/tier-1s/' . $this->vpnService->router->id .
                '/locale-services/' . $this->vpnService->router->id .
                '/ipsec-vpn-services?include_mark_for_delete_objects=true'
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'results' => [],
                ]));
            });

        Event::fake([JobFailed::class]);

        dispatch(new UndeployCheck($this->vpnService));

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testJobReleasedWhenStillExists()
    {
        $this->nsxServiceMock()->expects('get')
            ->withArgs([
                'policy/api/v1/infra/tier-1s/' . $this->vpnService->router->id .
                '/locale-services/' . $this->vpnService->router->id .
                '/ipsec-vpn-services?include_mark_for_delete_objects=true'
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'results' => [
                        [
                            'id' => $this->vpnService->id
                        ],
                    ],
                ]));
            });

        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new UndeployCheck($this->vpnService));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return $event->job->isReleased();
        });
    }
}
