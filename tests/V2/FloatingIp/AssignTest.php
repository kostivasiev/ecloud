<?php

namespace Tests\V2\FloatingIp;

use App\Events\V2\Task\Created;
use App\Models\V2\IpAddress;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class AssignTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
    }

    public function testSuccess()
    {
        Event::fake([Created::class]);

        $ipAddress = IpAddress::factory()->create([
            'network_id' => $this->network()->id
        ]);
        $ipAddress->nics()->sync($this->nic());

        $this->post('/v2/floating-ips/' . $this->floatingIp()->id .'/assign', [
            'resource_id' => $ipAddress->id
        ])->assertResponseStatus(202);

        Event::assertDispatched(\App\Events\V2\Task\Created::class, function ($event) {
            return $event->model->name == 'floating_ip_assign';
        });
    }

    public function testAlreadyAssigned()
    {
        $ipAddress = IpAddress::factory()->create([
            'network_id' => $this->network()->id
        ]);
        $ipAddress->nics()->sync($this->nic());

        $this->floatingIp()->resource()->associate($ipAddress)->save();

        $this->post('/v2/floating-ips/' . $this->floatingIp()->id .'/assign', [
            'resource_id' => $ipAddress->id
        ])->assertResponseStatus(409);
    }
}
