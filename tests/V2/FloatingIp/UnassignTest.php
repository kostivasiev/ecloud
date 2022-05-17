<?php

namespace Tests\V2\FloatingIp;

use App\Events\V2\Task\Created;
use App\Models\V2\FloatingIpResource;
use App\Models\V2\VpnEndpoint;
use App\Support\Sync;
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

        $floatingIpResource = app()->make(FloatingIpResource::class);
        $floatingIpResource->floatingIp()->associate($this->floatingIp());
        $floatingIpResource->resource()->associate($this->ip());
        $floatingIpResource->save();

        $this->post('/v2/floating-ips/' . $this->floatingIp()->id .'/unassign')
            ->assertStatus(202);

        Event::assertDispatched(\App\Events\V2\Task\Created::class, function ($event) {
            return $event->model->name == Sync::TASK_NAME_DELETE;
        });
    }

    public function testVpnEndpointFloatingIpCanNotBeUnassigned()
    {
        $vpnEndpoint = VpnEndpoint::factory()->create();

        $this->floatingIp()->resource()->associate($vpnEndpoint)->save();

        $this->post('/v2/floating-ips/' . $this->floatingIp()->id .'/unassign', [
            'resource_id' => $this->nic()->id
        ])
            ->assertStatus(403);
    }

    public function testVipFloatingIpCanNotBeUnassigned()
    {
        $clusterIp = $this->vip()->assignClusterIp();

        $this->floatingIp()->resource()->associate($clusterIp)->save();

        $this->post('/v2/floating-ips/' . $this->floatingIp()->id .'/unassign', [
            'resource_id' => $this->nic()->id
        ])->assertStatus(403);
    }
}
