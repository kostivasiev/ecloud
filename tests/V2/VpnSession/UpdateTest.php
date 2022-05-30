<?php
namespace Tests\V2\VpnSession;

use App\Events\V2\Task\Created;
use App\Models\V2\Credential;
use App\Models\V2\FloatingIp;
use App\Models\V2\VpnEndpoint;
use App\Models\V2\VpnProfileGroup;
use App\Models\V2\VpnService;
use App\Models\V2\VpnSession;
use App\Models\V2\VpnSessionNetwork;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class UpdateTest extends TestCase
{
    protected VpnService $vpnService;
    protected VpnEndpoint $vpnEndpoint;
    protected VpnSession $vpnSession;
    protected VpnProfileGroup $vpnProfileGroup;
    protected FloatingIp $floatingIp;
    protected string $preSharedKey;

    public function setUp(): void
    {
        parent::setUp();

        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
        $this->floatingIp = FloatingIp::withoutEvents(function () {
            return FloatingIp::factory()->create([
                'id' => 'fip-abc123xyz',
                'vpc_id' => $this->vpc()->id,
            ]);
        });
        $this->vpnService = VpnService::factory()->create([
            'router_id' => $this->router()->id,
        ]);

        $this->vpnEndpoint = VpnEndpoint::factory()->create();
        $this->floatingIp->resource()->associate($this->vpnEndpoint);
        $this->floatingIp->save();

        $this->vpnProfileGroup = VpnProfileGroup::factory()->create([
            'ike_profile_id' => 'ike-abc123xyz',
            'ipsec_profile_id' => 'ipsec-abc123xyz',
        ]);
        $this->vpnSession = VpnSession::factory()->create(
            [
                'vpn_profile_group_id' => $this->vpnProfileGroup->id,
                'vpn_service_id' => $this->vpnService->id,
                'vpn_endpoint_id' => $this->vpnEndpoint->id,
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
        $credential = new Credential(
            [
                'name' => 'Pre-shared Key for VPN Session ' . $this->vpnSession->id,
                'host' => null,
                'username' => VpnSession::CREDENTIAL_PSK_USERNAME,
                'password' => 'randomPasswordEnteredHere',
                'port' => null,
                'is_hidden' => true,
            ]
        );
        $this->vpnSession->credentials()->save($credential);
        $this->preSharedKey = 'ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAAAgQCuxFiJFGtRIxU7IZA35zya75IJokX21zVrM90rxdWykbZz9cb5obLMXGqPLiHDOKL2frUd9TtTvPI/OQzCu5Sd2x41PdYyLcjXoLAaPqlmUbi3ExzigDKWjVu7RCBYWNBIi63boq3SqUZRdf9oF/R81EGUsF8lMnEIoutDncH8jQ==';
    }

    public function testUpdateResource()
    {
        Event::fake([Created::class]);

        $data = [
            'name' => 'Updated Test Session',
        ];
        $this->patch(
            '/v2/vpn-sessions/' . $this->vpnSession->id,
            $data
        )->assertStatus(202);
        $this->assertDatabaseHas(
            'vpn_sessions',
            $data,
            'ecloud'
        );

        Event::assertDispatched(Created::class);
    }

    public function testUpdateResourceInvalidRemoteIp()
    {
        $this->patch(
            '/v2/vpn-sessions/' . $this->vpnSession->id,
            [
                'remote_ip' => 'INVALID_IP',
            ]
        )->assertJsonFragment([
            'detail' => 'The remote ip must be a valid IPv4 address',
        ])->assertStatus(422);
    }

    public function testUpdateResourceInvalidRemoteAndLocalNetworks()
    {
        $this->patch(
            '/v2/vpn-sessions/' . $this->vpnSession->id,
            [
                'remote_networks' => 'INVALID_IP',
                'local_networks' => 'INVALID_IP',
            ]
        )->assertJsonFragment([
            'detail' => 'The remote networks must contain a valid comma separated list of CIDR subnets',
        ])->assertJsonFragment([
            'detail' => 'The local networks must contain a valid comma separated list of CIDR subnets',
        ])->assertStatus(422);
    }

    public function testUpdateWithMaxLocalNetworksFails()
    {
        Config::set('vpn-session.max_local_networks', 2);

        $this->patch(
            '/v2/vpn-sessions/' . $this->vpnSession->id,
            [
                'local_networks' => '10.0.0.1/32,10.0.0.2/32,10.0.0.3/32',
            ]
        )->assertJsonFragment([
            'detail' => 'local networks must contain less than 2 comma-seperated items',
            'source' => 'local_networks',
        ])->assertStatus(422);
    }

    public function testUpdateWithMaxRemoteNetworksFails()
    {
        Config::set('vpn-session.max_remote_networks', 2);

        $this->patch(
            '/v2/vpn-sessions/' . $this->vpnSession->id,
            [
                'remote_networks' => '172.12.23.11/32,72.12.23.12/32,72.12.23.13/32',
            ]
        )->assertJsonFragment([
            'detail' => 'remote networks must contain less than 2 comma-seperated items',
            'source' => 'remote_networks',
        ])->assertStatus(422);
    }

    public function testUpdatePreSharedKey()
    {
        Event::fake([Created::class]);
        $response = $this->put(
            sprintf('/v2/vpn-sessions/%s/pre-shared-key', $this->vpnSession->id),
            [
                'psk' => $this->preSharedKey,
            ]
        );
        $this->assertEquals(202, $response->getStatusCode());
        Event::assertDispatched(Created::class);

        $this->vpnSession->refresh();
        $credential = $this->vpnSession
            ->credentials()
            ->where('username', VpnSession::CREDENTIAL_PSK_USERNAME)
            ->first();
        $this->assertDatabaseHas(
            Credential::class,
            [
                'id' => $credential->id,
                'password' => encrypt($this->preSharedKey),
            ],
            'ecloud'
        );
    }
}