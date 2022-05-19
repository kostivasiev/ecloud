<?php

namespace Tests\V2\FloatingIp;

use App\Events\V2\Task\Created;
use App\Jobs\Tasks\FloatingIp\Unassign;
use App\Models\V2\VpnEndpoint;
use Illuminate\Support\Facades\Event;
use Tests\Mocks\Resources\VipMock;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class UnassignTest extends TestCase
{
    use VipMock;

    public function setUp(): void
    {
        parent::setUp();
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
    }

    public function testSuccess()
    {
        Event::fake([Created::class]);

        $this->assignFloatingIp($this->floatingIp(), $this->ipAddress());

        $this->post('/v2/floating-ips/' . $this->floatingIp()->id .'/unassign')
            ->assertStatus(202);

        Event::assertDispatched(\App\Events\V2\Task\Created::class, function ($event) {
            return $event->model->name == Unassign::$name;
        });
    }

    public function testVpnEndpointFloatingIpCanNotBeUnassigned()
    {
        $vpnEndpoint = VpnEndpoint::factory()->create();

        $this->assignFloatingIp($this->floatingIp(), $vpnEndpoint);

        $this->post('/v2/floating-ips/' . $this->floatingIp()->id .'/unassign', [
            'resource_id' => $this->nic()->id
        ])
            ->assertStatus(403);
    }

    public function testVipFloatingIpCanNotBeUnassigned()
    {
        $clusterIp = $this->vip()->assignClusterIp();

        $this->assignFloatingIp($this->floatingIp(), $clusterIp);

        $this->post('/v2/floating-ips/' . $this->floatingIp()->id .'/unassign', [
            'resource_id' => $this->nic()->id
        ])->assertStatus(403);
    }
}
