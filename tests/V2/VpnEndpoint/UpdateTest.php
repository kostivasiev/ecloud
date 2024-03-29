<?php
namespace Tests\V2\VpnEndpoint;

use App\Events\V2\Task\Created;
use App\Models\V2\VpnEndpoint;
use App\Models\V2\VpnService;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class UpdateTest extends TestCase
{
    protected VpnEndpoint $vpnEndpoint;
    protected VpnService $vpnService;

    public function setUp(): void
    {
        parent::setUp();
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));

        $this->vpnService = VpnService::factory()->create([
            'router_id' => $this->router()->id,
        ]);
        $this->vpnEndpoint = VpnEndpoint::factory()->create(
            [
                'name' => 'Update Test',
                'vpn_service_id' => $this->vpnService->id,
            ]
        );

        $this->assignFloatingIp($this->floatingIp(), $this->vpnEndpoint);
    }

    public function testUpdateResource()
    {
        Event::fake(Created::class);
        $data = [
            'name' => 'Updated name',
        ];
        $this->patch('/v2/vpn-endpoints/' . $this->vpnEndpoint->id, $data)
            ->assertStatus(202);
        $this->assertDatabaseHas('vpn_endpoints', ['name' => $data['name']], 'ecloud');
    }

    public function testUpdateResourceWithSameData()
    {
        Event::fake(Created::class);
        $data = [
            'name' => $this->vpnEndpoint->name,
        ];
        $this->patch('/v2/vpn-endpoints/' . $this->vpnEndpoint->id, $data)
            ->assertStatus(202);
    }
}
