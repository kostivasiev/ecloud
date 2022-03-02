<?php
namespace Tests\V2\VpnSession;

use App\Events\V2\Task\Created;
use App\Models\V2\VpnEndpoint;
use App\Models\V2\VpnProfileGroup;
use App\Models\V2\VpnService;
use App\Models\V2\VpnSession;
use App\Models\V2\VpnSessionNetwork;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
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
        $this->vpnService = VpnService::factory()->create([
            'router_id' => $this->router()->id,
        ]);

        $this->vpnEndpoint = VpnEndpoint::factory()->create([
            'name' => 'Create Test',
            'vpn_service_id' => $this->vpnService->id,
        ]);
        $this->floatingIp()->resource()->associate($this->vpnEndpoint);
        $this->floatingIp()->save();

        $this->vpnProfileGroup = VpnProfileGroup::factory()->create([
            'availability_zone_id' => $this->availabilityZone()->id,
            'ike_profile_id' => 'ike-abc123xyz',
            'ipsec_profile_id' => 'ipsec-abc123xyz',
        ]);
        $this->vpnSession = VpnSession::factory()->create(
            [
                'vpn_profile_group_id' => $this->vpnProfileGroup->id,
                'remote_ip' => '211.12.13.1',
            ]
        );
        $this->vpnSession->vpnSessionNetworks()->create([
            'id' => 'vpnsn-local1',
            'type' => VpnSessionNetwork::TYPE_LOCAL,
            'ip_address' => '127.1.1.1/32',
        ]);
        $this->vpnSession->vpnSessionNetworks()->create([
            'id' => 'vpnsn-local2',
            'type' => VpnSessionNetwork::TYPE_LOCAL,
            'ip_address' => '127.1.10.1/24',
        ]);
        $this->vpnSession->vpnSessionNetworks()->create([
            'id' => 'vpnsn-remote1',
            'type' => VpnSessionNetwork::TYPE_REMOTE,
            'ip_address' => '127.1.1.1/32',
        ]);
    }

    public function testCreateResource()
    {
        Event::fake([Created::class]);
        $vpnService = VpnService::factory()->create([
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
                'local_networks' => '10.0.0.1/32',
                'remote_networks' => '172.12.23.11/32',
            ]
        )->assertStatus(202);
        Event::assertDispatched(Created::class);
    }

    public function testCreateResourceInvalidService()
    {
        $this->post(
            '/v2/vpn-sessions',
            [
                'name' => 'vpn session test',
                'vpn_profile_group_id' => $this->vpnProfileGroup->id,
                'vpn_service_id' => 'vpns-00000000',
                'vpn_endpoint_id' => $this->vpnEndpoint->id,
                'remote_ip' => '211.12.13.1',
                'local_networks' => '10.0.0.1/32',
                'remote_networks' => '172.12.23.11/32',
            ]
        )->assertJsonFragment(
            [
                'title' => 'Not found',
                'detail' => 'No Vpn Session with that ID was found',
            ]
        )->assertStatus(404);
    }

    public function testCreateResourceWithInvalidIps()
    {
        $service = VpnService::factory()->create([
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
        )->assertJsonFragment([
            'detail' => 'The remote ip must be a valid IPv4 address',
            'source' => 'remote_ip',
        ])->assertJsonFragment([
            'detail' => 'The remote networks must contain a valid comma separated list of CIDR subnets',
            'source' => 'remote_networks',
        ])->assertJsonFragment([
            'detail' => 'The local networks must contain a valid comma separated list of CIDR subnets',
            'source' => 'local_networks',
        ])->assertStatus(422);
    }

    public function testCreateResourceWithMissingLocalNetworks()
    {
        $service = VpnService::factory()->create([
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
                'remote_ip' => '211.12.13.1',
                'remote_networks' => '172.12.23.11/32',
            ]
        )->assertJsonFragment([
            'detail' => 'The local networks field is required',
            'source' => 'local_networks',
        ])->assertStatus(422);
    }

    public function testCreateResourceWithMissingRemoteNetworks()
    {
        $service = VpnService::factory()->create([
            'name' => 'test-service',
            'router_id' => $this->router()->id,
        ]);

        $response = $this->post(
            '/v2/vpn-sessions',
            [
                'name' => 'vpn session test',
                'vpn_profile_group_id' => $this->vpnProfileGroup->id,
                'vpn_service_id' => $service->id,
                'vpn_endpoint_id' => $this->vpnEndpoint->id,
                'remote_ip' => '211.12.13.1',
                'local_networks' => '172.12.23.11/32',
            ]
        )->assertJsonFragment([
            'detail' => 'The remote networks field is required',
            'source' => 'remote_networks',
        ])->assertStatus(422);
    }

    public function testCreateWithMaxLocalNetworksFails()
    {
        Config::set('vpn-session.max_local_networks', 2);

        $this->post(
            '/v2/vpn-sessions',
            [
                'name' => 'vpn session test',
                'vpn_profile_group_id' => $this->vpnProfileGroup->id,
                'vpn_service_id' => $this->vpnService->id,
                'vpn_endpoint_id' => $this->vpnEndpoint->id,
                'remote_ip' => '211.12.13.1',
                'local_networks' => '10.0.0.1/32,10.0.0.2/32,10.0.0.3/32',
                'remote_networks' => '172.12.23.11/32',
            ]
        )->assertJsonFragment([
            'detail' => 'local networks must contain less than 2 comma-seperated items',
            'source' => 'local_networks',
        ])->assertStatus(422);
    }

    public function testCreateWithMaxRemoteNetworksFails()
    {
        Config::set('vpn-session.max_remote_networks', 2);

        $this->post(
            '/v2/vpn-sessions',
            [
                'name' => 'vpn session test',
                'vpn_profile_group_id' => $this->vpnProfileGroup->id,
                'vpn_service_id' => $this->vpnService->id,
                'vpn_endpoint_id' => $this->vpnEndpoint->id,
                'remote_ip' => '211.12.13.1',
                'local_networks' => '10.0.0.1/32',
                'remote_networks' => '172.12.23.11/32,72.12.23.12/32,72.12.23.13/32',
            ]
        )->assertJsonFragment([
            'detail' => 'remote networks must contain less than 2 comma-seperated items',
            'source' => 'remote_networks',
        ])->assertStatus(422);
    }
}