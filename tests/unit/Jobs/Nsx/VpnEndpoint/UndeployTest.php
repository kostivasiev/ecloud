<?php

namespace Tests\unit\Jobs\Nsx\VpnEndpoint;

use App\Jobs\Nsx\VpnEndpoint\Undeploy;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Tests\Mocks\Resources\VpnEndpointMock;
use Tests\Mocks\Resources\VpnServiceMock;
use Tests\TestCase;

class UndeployTest extends TestCase
{
    use VpnServiceMock, VpnEndpointMock;

    public function testSuccess()
    {
        $this->nsxServiceMock()->expects('delete')
            ->withArgs([
                '/policy/api/v1/infra/tier-1s/' . $this->router()->id .
                '/locale-services/' . $this->router()->id .
                '/ipsec-vpn-services/' . $this->vpnService()->id .
                '/local-endpoints/' . $this->vpnEndpoint()->id
            ])
            ->andReturnUsing(function () {
                return new Response(200);
            });

        Event::fake([JobFailed::class]);

        dispatch(new Undeploy($this->vpnEndpoint()));

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testFailure()
    {
        $this->expectException(RequestException::class);
        $this->nsxServiceMock()->expects('delete')
            ->withArgs([
                '/policy/api/v1/infra/tier-1s/' . $this->router()->id .
                '/locale-services/' . $this->router()->id .
                '/ipsec-vpn-services/' . $this->vpnService()->id .
                '/local-endpoints/' . $this->vpnEndpoint()->id
            ])
            ->andThrow(new RequestException('Bad Request', new Request('delete', '/'), new Response(400, [], 'Bad Request')));

        Event::fake([JobFailed::class]);

        dispatch(new Undeploy($this->vpnEndpoint()));

        Event::assertDispatched(JobFailed::class);
    }
}
