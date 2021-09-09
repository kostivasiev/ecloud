<?php
namespace Tests\V2\VpnSession;

use App\Models\V2\Credential;
use App\Models\V2\FloatingIp;
use App\Models\V2\VpnEndpoint;
use App\Models\V2\VpnProfileGroup;
use App\Models\V2\VpnService;
use App\Models\V2\VpnSession;
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
            return factory(FloatingIp::class)->create([
                'id' => 'fip-abc123xyz',
            ]);
        });
        $this->vpnService = factory(VpnService::class)->create([
            'router_id' => $this->router()->id,
        ]);

        $this->vpnEndpoint = factory(VpnEndpoint::class)->create();
        $this->floatingIp->resource()->associate($this->vpnEndpoint);
        $this->floatingIp->save();

        $this->vpnProfileGroup = factory(VpnProfileGroup::class)->create([
            'availability_zone_id' => $this->availabilityZone()->id,
            'ike_profile_id' => 'ike-abc123xyz',
            'ipsec_profile_id' => 'ipsec-abc123xyz',
            'dpd_profile_id' => 'dpd-abc123xyz',
        ]);
        $this->vpnSession = factory(VpnSession::class)->create(
            [
                'vpn_profile_group_id' => $this->vpnProfileGroup->id,
                'vpn_service_id' => $this->vpnService->id,
                'vpn_endpoint_id' => $this->vpnEndpoint->id,
                'remote_ip' => '211.12.13.1',
                'remote_networks' => '127.1.1.1/32',
                'local_networks' => '127.1.1.1/32,127.1.10.1/24',
            ]
        );
        $this->credential = factory(Credential::class)->create([
            'resource_id' => $this->vpnSession->id,
            'username' => VpnSession::CREDENTIAL_PSK_USERNAME
        ]);
    }

    public function testGetCollection()
    {
        $this->get('/v2/vpn-sessions')
            ->seeJson(
                [
                    'id' => $this->vpnSession->id,
                    'vpn_profile_group_id' => $this->vpnProfileGroup->id,
                ]
            )->assertResponseStatus(200);
    }

    public function testGetResource()
    {
        $this->get('/v2/vpn-sessions/' . $this->vpnSession->id)
            ->seeJson(
                [
                    'id' => $this->vpnSession->id,
                    'vpn_profile_group_id' => $this->vpnProfileGroup->id,
                ]
            )->assertResponseStatus(200);
    }

    public function testGetPreSharedKeySucceedsWhenExists()
    {
        $this->get('/v2/vpn-sessions/' . $this->vpnSession->id . '/pre-shared-key')
            ->seeJson(
                [
                    'psk' => 'somepassword',
                ]
            )
            ->assertResponseStatus(200);
    }

    public function testGetPreSharedKey404WhenNotExist()
    {
        $this->credential->delete();

        $this->get('/v2/vpn-sessions/' . $this->vpnSession->id . '/pre-shared-key')
            ->assertResponseStatus(404);
    }
}