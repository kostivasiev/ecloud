<?php

namespace Tests\unit\Jobs\Nsx\VpnService;

use App\Jobs\Nsx\VpnService\Undeploy;
use App\Models\V2\VpnService;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class UndeployTest extends TestCase
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

    public function testVpnRemovedWhenExists()
    {
        $this->nsxServiceMock()->expects('delete')
            ->withArgs([
                '/policy/api/v1/infra/tier-1s/' . $this->vpnService->router->id .
                '/locale-services/' . $this->vpnService->router->id .
                '/ipsec-vpn-services/' . $this->vpnService->id
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([]));
            });

        Event::fake([JobFailed::class]);

        dispatch(new Undeploy($this->vpnService));

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testSkippedWhenDoesntExist()
    {
        $this->nsxServiceMock()->expects('delete')
            ->withArgs([
                '/policy/api/v1/infra/tier-1s/' . $this->vpnService->router->id .
                '/locale-services/' . $this->vpnService->router->id .
                '/ipsec-vpn-services/' . $this->vpnService->id
            ])
            ->andThrow(
                new ClientException('Not Found', new Request('GET', 'test'), new Response(404))
            );
        $this->nsxServiceMock()->shouldNotReceive('delete');

        Event::fake([JobFailed::class]);

        dispatch(new Undeploy($this->vpnService));

        Event::assertNotDispatched(JobFailed::class);
    }
}
