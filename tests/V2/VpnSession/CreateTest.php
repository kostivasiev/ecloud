<?php
namespace Tests\V2\VpnSession;

use App\Models\V2\FloatingIp;
use App\Models\V2\VpnEndpoint;
use App\Models\V2\VpnProfileGroup;
use App\Models\V2\VpnService;
use App\Models\V2\VpnSession;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class CreateTest extends TestCase
{
    protected VpnService $vpnService;
    protected VpnEndpoint $vpnEndpoint;
    protected VpnSession $vpnSession;
    protected VpnProfileGroup $vpnProfileGroup;

    public function setUp(): void
    {
        parent::setUp();

        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
        $this->vpnService = factory(VpnService::class)->create([
            'router_id' => $this->router()->id,
        ]);

        $this->vpnEndpoint = factory(VpnEndpoint::class)->create([
            'name' => 'Create Test',
            'vpn_service_id' => $this->vpnService->id,
        ]);
        $this->floatingIp()->resource()->associate($this->vpnEndpoint);
        $this->floatingIp()->save();

        $this->vpnProfileGroup = factory(VpnProfileGroup::class)->create([
            'ike_profile_id' => 'ike-abc123xyz',
            'ipsec_profile_id' => 'ipsec-abc123xyz',
            'dpd_profile_id' => 'dpd-abc123xyz',
        ]);
        $this->vpnSession = factory(VpnSession::class)->create(
            [
                'vpn_profile_group_id' => $this->vpnProfileGroup->id,
                'remote_ip' => '211.12.13.1',
                'remote_networks' => '127.1.1.1/32',
                'local_networks' => '127.1.1.1/32,127.1.10.1/24',
            ]
        );
    }

    public function testCreateResource()
    {
        $counter = 0;
        $vpnService = factory(VpnService::class)->create([
            'name' => 'test-service',
            'router_id' => $this->router()->id,
        ]);

        $this->post(
            '/v2/vpn-sessions',
            [
                'name' => 'vpn session test',
                'vpn_profile_group_id' => $this->vpnProfileGroup->id,
                'vpn_service_id' => $vpnService->id,
                'vpn_endpoint_id' => $this->vpnEndpoint->id,
                'remote_ip' => '211.12.13.1',
                'remote_networks' => '172.12.23.11/32',
            ]
        )->assertResponseStatus(202);
    }

    public function testCreateResourceInvalidService()
    {
        $this->post(
            '/v2/vpn-sessions',
            [
                'name' => 'vpn session test',
                'vpn_profile_group_id' => $this->vpnProfileGroup->id,
                'vpn_service_id' => 'vnps-00000000',
                'vpn_endpoint_id' => $this->vpnEndpoint->id,
                'remote_ip' => '211.12.13.1',
                'remote_networks' => '172.12.23.11/32',
            ]
        )->seeJson(
            [
                'title' => 'Validation Error',
                'detail' => 'The selected vpn service id is invalid',
            ]
        )->assertResponseStatus(422);
    }

    public function testCreateResourceWithInvalidIps()
    {
        $service = factory(VpnService::class)->create([
            'name' => 'test-service',
            'router_id' => $this->router()->id,
        ]);

        $this->post(
            '/v2/vpn-sessions',
            [
                'name' => 'vpn session test',
                'vpn_profile_group_id' => $this->vpnProfileGroup->id,
                'vpn_service_id' => $service->id,
                'vpn_endpoint_id' => $this->vpnEndpoint->id,
                'remote_ip' => 'INVALID',
                'remote_networks' => 'INVALID',
                'local_networks' => 'INVALID',
            ]
        )->seeJson([
            'detail' => 'The remote ip must be a valid IPv4 address',
            'source' => 'remote_ip',
        ])->seeJson([
            'detail' => 'The remote networks must contain a valid comma separated list of CIDR subnets',
            'source' => 'remote_networks',
        ])->seeJson([
            'detail' => 'The local networks must contain a valid comma separated list of CIDR subnets',
            'source' => 'local_networks',
        ])->assertResponseStatus(422);
    }
}