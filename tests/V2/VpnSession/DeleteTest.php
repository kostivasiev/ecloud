<?php
namespace Tests\V2\VpnSession;

use App\Events\V2\Task\Created;
use App\Models\V2\FloatingIp;
use App\Models\V2\VpnEndpoint;
use App\Models\V2\VpnProfileGroup;
use App\Models\V2\VpnService;
use App\Models\V2\VpnSession;
use App\Models\V2\VpnSessionNetwork;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class DeleteTest extends TestCase
{

    protected FloatingIp $floatingIp;
    protected VpnService $vpnService;
    protected VpnEndpoint $vpnEndpoint;
    protected VpnProfileGroup $vpnProfileGroup;
    protected VpnSession $vpnSession;

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
        $this->floatingIp->resource()->associate($this->vpnEndpoint);
        $this->floatingIp->save();

        $this->vpnProfileGroup = VpnProfileGroup::factory()->create([
            'ike_profile_id' => 'ike-abc123xyz',
            'ipsec_profile_id' => 'ipsec-abc123xyz'
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
    }

    public function testDeleteResource()
    {
        Event::fake(Created::class);

        $this->delete('/v2/vpn-sessions/' . $this->vpnSession->id)
            ->assertStatus(202);

        Event::assertDispatched(Created::class);
    }
}