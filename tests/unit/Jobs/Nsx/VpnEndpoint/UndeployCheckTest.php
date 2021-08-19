<?php

namespace Tests\unit\Jobs\Nsx\VpnEndpoint;

use App\Jobs\Nsx\VpnEndpoint\UndeployCheck;
use GuzzleHttp\Psr7\Response;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Tests\Mocks\Resources\VpnEndpointMock;
use Tests\Mocks\Resources\VpnServiceMock;
use Tests\TestCase;

class UndeployCheckTest extends TestCase
{
    use VpnServiceMock, VpnEndpointMock;

    public function testNotFoundSucceeds()
    {
        Event::fake([JobFailed::class, JobProcessed::class]);

        $this->nsxServiceMock()->expects('get')
            ->withArgs([
                '/policy/api/v1/infra/tier-1s/' . $this->vpnService()->router->id .
                '/locale-services/' . $this->vpnService()->router->id .
                '/ipsec-vpn-services/' . $this->vpnService()->id .
                '/local-endpoints?include_mark_for_delete_objects=true'
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'results' => [],
                ]));
            });

        dispatch(new UndeployCheck($this->vpnEndpoint()));

        Event::assertNotDispatched(JobFailed::class);

        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }

    public function testJobReleasedWhenStillExists()
    {
        Event::fake([JobFailed::class, JobProcessed::class]);

        $this->nsxServiceMock()->expects('get')
            ->withArgs([
                '/policy/api/v1/infra/tier-1s/' . $this->vpnService()->router->id .
                '/locale-services/' . $this->vpnService()->router->id .
                '/ipsec-vpn-services/' . $this->vpnService()->id .
                '/local-endpoints?include_mark_for_delete_objects=true'
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'results' => [
                        [
                            'id' => $this->vpnEndpoint()->id
                        ],
                    ],
                ]));
            });

        dispatch(new UndeployCheck($this->vpnEndpoint()));

        Event::assertNotDispatched(JobFailed::class);

        Event::assertDispatched(JobProcessed::class, function ($event) {
            return $event->job->isReleased();
        });
    }
}
