<?php

namespace Tests\Unit\Jobs\VpnEndpoint;

use App\Events\V2\Task\Created;
use App\Jobs\VpnEndpoint\CreateFloatingIp;
use App\Models\V2\FloatingIp;
use App\Models\V2\Nat;
use App\Models\V2\Task;
use App\Support\Sync;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Tests\Mocks\Resources\VpnEndpointMock;
use Tests\Mocks\Resources\VpnServiceMock;
use Tests\TestCase;

class CreateFloatingIpTest extends TestCase
{
    use VpnServiceMock, VpnEndpointMock;

    protected CreateFloatingIp $job;

    public function testSuccessful()
    {
        app()->bind(FloatingIp::class, function () {
            return $this->floatingIp();
        });

        Event::fake([Created::class]);

        $this->assertFalse($this->vpnEndpoint()->floatingIpResource()->exists());

        $task = Task::withoutEvents(function () {
            $task = new Task([
                'id' => 'task-1',
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $task->resource()->associate($this->vpnEndpoint());
            $task->save();
            return $task;
        });

        $this->assertNull($task->data);

        dispatch(new CreateFloatingIp($task));

        Event::assertNotDispatched(JobFailed::class);

        $this->vpnEndpoint()->refresh();

        Event::assertDispatched(Created::class, function ($event) {
            return $event->model->name == Sync::TASK_NAME_UPDATE
                && $event->model->resource instanceof FloatingIp;
        });

        $task->refresh();

        $this->assertEquals($this->floatingIp()->id, $task->data['floating_ip_id']);
    }

    public function testWhenFipAlreadyAssigned()
    {
        app()->bind(FloatingIp::class, function () {
            return $this->floatingIp();
        });

        Event::fake([Created::class]);

        $this->assertFalse($this->vpnEndpoint()->floatingIpResource()->exists());

        $task = Task::withoutEvents(function () {
            $task = new Task([
                'id' => 'task-1',
                'name' => Sync::TASK_NAME_UPDATE,
                'data' => [
                    'floating_ip_id' => $this->floatingIp()->id,
                ]
            ]);
            $task->resource()->associate($this->vpnEndpoint());
            $task->save();
            return $task;
        });

        $this->assignFloatingIp($this->floatingIp(), $this->vpnEndpoint());

        dispatch(new CreateFloatingIp($task));

        Event::assertNotDispatched(JobFailed::class);

        Event::assertNotDispatched(Created::class);
    }
}