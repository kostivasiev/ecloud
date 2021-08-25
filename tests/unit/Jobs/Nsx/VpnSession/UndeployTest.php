<?php

namespace Tests\unit\Jobs\Nsx\VpnSession;

use App\Jobs\Nsx\VpnSession\Undeploy;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Tests\Mocks\Resources\VpnSessionMock;
use Tests\TestCase;

class UndeployTest extends TestCase
{
    use VpnSessionMock;

    public function testSuccess()
    {
        $this->nsxServiceMock()->expects('delete')
            ->withArgs([
                '/policy/api/v1/infra/tier-1s/' . $this->router()->id .
                '/locale-services/' . $this->router()->id .
                '/ipsec-vpn-services/' . $this->vpnService()->id .
                '/sessions/' . $this->vpnSession()->id,
            ])
            ->andReturnUsing(function () {
                return new Response(200);
            });

        Event::fake([JobFailed::class]);

        dispatch(new Undeploy($this->vpnSession()));

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
                '/sessions/' . $this->vpnSession()->id,
            ])
            ->andThrow(new RequestException('Bad Request', new Request('delete', '/'), new Response(400, [], 'Bad Request')));

        Event::fake([JobFailed::class]);

        dispatch(new Undeploy($this->vpnSession()));

        Event::assertDispatched(JobFailed::class);
    }
}
