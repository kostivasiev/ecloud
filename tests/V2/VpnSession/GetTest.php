<?php
namespace Tests\V2\VpnSession;

use App\Models\V2\Credential;
use App\Models\V2\FloatingIp;
use App\Models\V2\VpnEndpoint;
use App\Models\V2\VpnProfileGroup;
use App\Models\V2\VpnService;
use App\Models\V2\VpnSession;
use App\Models\V2\VpnSessionNetwork;
use GuzzleHttp\Psr7\Response;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class GetTest extends TestCase
{
    protected Credential $credential;
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
            return FloatingIp::factory()->create([
                'id' => 'fip-abc123xyz',
            ]);
        });
        $this->vpnService = VpnService::factory()->create([
            'router_id' => $this->router()->id,
        ]);

        $this->vpnEndpoint = VpnEndpoint::factory()->create();

        $this->assignFloatingIp($this->floatingIp, $this->vpnEndpoint);

        $this->vpnProfileGroup = VpnProfileGroup::factory()->create([
            'availability_zone_id' => $this->availabilityZone()->id,
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
        $this->credential = Credential::factory()->create([
            'resource_id' => $this->vpnSession->id,
            'username' => VpnSession::CREDENTIAL_PSK_USERNAME
        ]);
    }

    public function testGetCollection()
    {
        $this->get('/v2/vpn-sessions')
            ->assertJsonFragment(
                [
                    'id' => $this->vpnSession->id,
                    'vpn_profile_group_id' => $this->vpnProfileGroup->id,
                ]
            )->assertStatus(200);
    }

    public function testGetResource()
    {
        $this->nsxServiceMock()->expects('get')
            ->withArgs([
                '/policy/api/v1/infra/tier-1s/rtr-test/locale-services/rtr-test/ipsec-vpn-services/' . $this->vpnSession->vpnService->id . '/sessions/' . $this->vpnSession->id . '/statistics',
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([]));
            });

        $this->get('/v2/vpn-sessions/' . $this->vpnSession->id)
            ->assertJsonFragment(
                [
                    'id' => $this->vpnSession->id,
                    'vpn_profile_group_id' => $this->vpnProfileGroup->id,
                ]
            )->assertStatus(200);
    }

    public function testGetPreSharedKeySucceedsWhenExists()
    {
        $this->get('/v2/vpn-sessions/' . $this->vpnSession->id . '/pre-shared-key')
            ->assertJsonFragment(
                [
                    'psk' => 'somepassword',
                ]
            )
            ->assertStatus(200);
    }

    public function testGetPreSharedKey404WhenNotExist()
    {
        $this->credential->delete();

        $this->get('/v2/vpn-sessions/' . $this->vpnSession->id . '/pre-shared-key')
            ->assertStatus(404);
    }

    public function testVpcIdFiltering()
    {
        $this->get('/v2/vpn-sessions?vpc_id:eq=' . $this->vpc()->id)
            ->assertJsonFragment([
                'vpc_id' => $this->vpc()->id,
            ])->assertJsonFragment([
                'count' => 1,
            ])->assertStatus(200);

        $this->get('/v2/vpn-sessions?vpc_id:neq=' . $this->vpc()->id)
            ->assertJsonFragment([
                'count' => 0,
            ])->assertStatus(200);
    }
}