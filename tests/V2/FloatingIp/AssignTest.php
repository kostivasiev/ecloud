<?php

namespace Tests\V2\FloatingIp;

use App\Events\V2\Task\Created;
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

    public function testNicAssignsNatsSuccess()
    {
        Event::fake([Created::class]);

        $this->post('/v2/floating-ips/' . $this->floatingIp()->id .'/assign', [
            'resource_id' => $this->nic()->id
        ])
            ->assertResponseStatus(202);

        Event::assertDispatched(\App\Events\V2\Task\Created::class, function ($event) {
            return $event->model->name == 'sync_update';
        });
    }

    public function testSuccess()
    {
        Event::fake([Created::class]);

        $this->post('/v2/floating-ips/' . $this->floatingIp()->id .'/assign', [
            'resource_id' => $this->nic()->id
        ])
            ->seeInDatabase('floating_ips', [
                'id' => $this->floatingIp()->id,
                'resource_id' => $this->nic()->id
            ], 'ecloud')
            ->assertResponseStatus(202);

        Event::assertDispatched(\App\Events\V2\Task\Created::class, function ($event) {
            return $event->model->name == 'sync_update';
        });
    }

    public function testAlreadyAssigned()
    {
        $this->floatingIp()->resource()->associate($this->nic())->save();

        $this->post('/v2/floating-ips/' . $this->floatingIp()->id .'/assign', [
            'resource_id' => $this->nic()->id
        ])->assertResponseStatus(409);
    }
}
