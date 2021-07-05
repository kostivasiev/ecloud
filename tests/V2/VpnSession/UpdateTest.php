<?php
namespace Tests\V2\VpnSession;

use App\Models\V2\FloatingIp;
use App\Models\V2\VpnEndpoint;
use App\Models\V2\VpnProfileGroup;
use App\Models\V2\VpnService;
use App\Models\V2\VpnSession;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class UpdateTest extends TestCase
{
    protected VpnService $vpnService;
    protected VpnEndpoint $vpnEndpoint;
    protected VpnSession $vpnSession;
    protected VpnProfileGroup $vpnProfileGroup;
    protected FloatingIp $floatingIp;

    public function setUp(): void
    {
        parent::setUp();

        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
        $this->floatingIp = FloatingIp::withoutEvents(function () {
            return factory(FloatingIp::class)->create([
                'id' => 'fip-abc123xyz',
                'vpc_id' => $this->vpc()->id,
            ]);
        });
        $this->vpnService = factory(VpnService::class)->create([
            'router_id' => $this->router()->id,
        ]);
        $this->vpnEndpoint = factory(VpnEndpoint::class)->create([
            'fip_id' => $this->floatingIp->id,
        ]);
        $this->vpnProfileGroup = factory(VpnProfileGroup::class)->create([
            'ike_profile_id' => 'ike-abc123xyz',
            'ipsec_profile_id' => 'ipsec-abc123xyz',
            'dpd_profile_id' => 'dpd-abc123xyz',
        ]);
        $this->vpnSession = factory(VpnSession::class)->create(
            [
                'vpn_profile_group_id' => $this->vpnProfileGroup->id,
                'vpn_service_id' => $this->vpnService->id,
                'remote_ip' => '211.12.13.1',
                'remote_networks' => '127.1.1.1/32',
                'local_networks' => '127.1.1.1/32,127.1.10.1/24',
            ]
        );
    }

    public function testUpdateResource()
    {
        $this->vpnSession->vpnEndpoints()->attach($this->vpnEndpoint);
        $data = [
            'name' => 'Updated Test Session',
        ];
        $this->patch(
            '/v2/vpn-sessions/' . $this->vpnSession->id,
            $data
        )->seeInDatabase(
            'vpn_sessions',
            $data,
            'ecloud'
        )->assertResponseStatus(202);
    }

    public function testUpdateResourceEndpointUsed()
    {
        $this->vpnSession->vpnEndpoints()->attach($this->vpnEndpoint);
        $this->patch(
            '/v2/vpn-sessions/' . $this->vpnSession->id,
            [
                'vpn_endpoint_id' => [
                    $this->vpnEndpoint->id,
                ]
            ]
        )->seeJson([
            'title' => 'Validation Error',
            'detail' => 'The vpn_endpoint_id.0 is already in use for this session',
        ])->assertResponseStatus(422);
    }

    public function testUpdateResourceInvalidRemoteIp()
    {
        $this->patch(
            '/v2/vpn-sessions/' . $this->vpnSession->id,
            [
                'remote_ip' => 'INVALID_IP',
            ]
        )->seeJson([
            'detail' => 'The remote ip must be a valid IPv4 address',
        ])->assertResponseStatus(422);
    }

    public function testUpdateResourceInvalidRemoteAndLocalNetworks()
    {
        $this->patch(
            '/v2/vpn-sessions/' . $this->vpnSession->id,
            [
                'remote_networks' => 'INVALID_IP',
                'local_networks' => 'INVALID_IP',
            ]
        )->seeJson([
            'detail' => 'The remote networks must contain a valid comma separated list of CIDR subnets',
        ])->seeJson([
            'detail' => 'The local networks must contain a valid comma separated list of CIDR subnets',
        ])->assertResponseStatus(422);
    }
}