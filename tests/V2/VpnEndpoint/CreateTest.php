<?php
namespace Tests\V2\VpnEndpoint;

use App\Events\V2\Task\Created;
use App\Models\V2\FloatingIp;
use App\Models\V2\VpnEndpoint;
use App\Models\V2\VpnService;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class CreateTest extends TestCase
{
    protected FloatingIp $floatingIp;
    protected VpnService $vpnService;

    public function setUp(): void
    {
        parent::setUp();
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
        $this->floatingIp = FloatingIp::withoutEvents(function () {
            return FloatingIp::factory()->create([
                'id' => 'fip-abc123xyz',
                'vpc_id' => $this->vpc()->id,
                'ip_address' => '203.0.113.1',
                'availability_zone_id' => $this->availabilityZone()->id,
            ]);
        });
        $this->vpnService = VpnService::factory()->create([
            'router_id' => $this->router()->id,
        ]);
    }

    public function testCreateResource()
    {
        Event::fake(Created::class);
        $data = [
            'name' => 'Create Test',
            'vpn_service_id' => $this->vpnService->id,
            'floating_ip_id' => $this->floatingIp->id,
        ];
        $this->post('/v2/vpn-endpoints', $data)
            ->assertStatus(202);
    }

    public function testCreateResourceFipInUse()
    {
        $vpnService = VpnService::factory()->create([
            'router_id' => $this->router()->id,
        ]);

        $this->assignFloatingIp($this->floatingIp, $this->ipAddress());

        $data = [
            'name' => 'Create Test',
            'vpn_service_id' => $vpnService->id,
            'floating_ip_id' => $this->floatingIp->id,
        ];
        $this->post('/v2/vpn-endpoints', $data)
            ->assertJsonFragment(
                [
                    'error' => 'The Floating IP is already assigned to a resource.',
                ]
            )
            ->assertStatus(422);
    }
}
