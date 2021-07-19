<?php

namespace Tests\V2\FloatingIp;

use App\Events\V2\Task\Created;
use App\Models\V2\VpnEndpoint;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class UnassignTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
    }

    public function testSuccess()
    {
        Event::fake([Created::class]);

        $this->floatingIp()->resource()->associate($this->nic())->save();

        $this->seeInDatabase('floating_ips', [
            'id' => $this->floatingIp()->id,
            'resource_id' => $this->nic()->id
        ], 'ecloud');

        $this->post('/v2/floating-ips/' . $this->floatingIp()->id .'/unassign')
            ->notSeeInDatabase('floating_ips', [
                'resource_id' => $this->nic()->id
            ], 'ecloud')
            ->assertResponseStatus(202);

        Event::assertDispatched(\App\Events\V2\Task\Created::class, function ($event) {
            return $event->model->name == 'sync_update';
        });
    }

    public function testVpnEndpointFloatingIpCanNotBeUnassigned()
    {
        $vpnEndpoint = factory(VpnEndpoint::class)->create();

        $this->floatingIp()->resource()->associate($vpnEndpoint)->save();

        $this->post('/v2/floating-ips/' . $this->floatingIp()->id .'/unassign', [
            'resource_id' => $this->nic()->id
        ])
            ->assertResponseStatus(403);
    }
}
