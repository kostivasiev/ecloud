<?php
namespace Tests\V2\VpnEndpoint;

use App\Models\V2\FloatingIp;
use App\Models\V2\VpnEndpoint;
use App\Models\V2\VpnService;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class DeleteTest extends TestCase
{
    protected VpnEndpoint $vpnEndpoint;
    protected VpnService $vpnService;

    public function setUp(): void
    {
        parent::setUp();
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
        $floatingIp = FloatingIp::withoutEvents(function () {
            return factory(FloatingIp::class)->create([
                'id' => 'fip-abc123xyz',
                'vpc_id' => $this->vpc()->id,
                'ip_address' => '203.0.113.1',
            ]);
        });
        $this->vpnService = factory(VpnService::class)->create([
            'router_id' => $this->router()->id,
        ]);
        $this->vpnEndpoint = factory(VpnEndpoint::class)->create(
            [
                'name' => 'Get Test',
                'vpn_service_id' => $this->vpnService->id,
                'nsx_uuid' => '825a690b-6bce-4dbc-be46-0230948c89da',
            ]
        );
        $floatingIp->resource()->associate($this->vpnEndpoint);
        $floatingIp->save();
    }

    public function testDeleteResource()
    {
        $this->nsxServiceMock()->shouldReceive('delete')
            ->withSomeOfArgs('/api/v1/vpn/ipsec/local-endpoints/'.$this->vpnEndpoint->nsx_uuid)
            ->andReturnTrue();
        $this->delete('/v2/vpn-endpoints/' . $this->vpnEndpoint->id)
            ->assertResponseStatus(202);
    }

    public function testDeleteResourceWrongUser()
    {
        $this->be(new Consumer(999, [config('app.name') . '.read', config('app.name') . '.write']));
        $this->delete('/v2/vpn-endpoints/' . $this->vpnEndpoint->id)
            ->seeJson(
                [
                    'title' => 'Not found',
                    'detail' => 'No Vpn Endpoint with that ID was found',
                ]
            )->assertResponseStatus(404);
    }
}