<?php

namespace Tests\unit\Jobs\VpnEndpoint;

use App\Events\V2\Task\Created;
use App\Jobs\VpnEndpoint\UnassignFloatingIP;
use App\Models\V2\FloatingIp;
use App\Models\V2\Task;
use App\Support\Sync;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Tests\Mocks\Resources\VpnEndpointMock;
use Tests\Mocks\Resources\VpnServiceMock;
use Tests\TestCase;

class UnassignFloatingIpTest extends TestCase
{
    use VpnServiceMock, VpnEndpointMock;

    protected Task $task;

    public function testNoFloatingIpAssignedSucceeds()
    {
        Model::withoutEvents(function () {
            $this->task = new Task([
                'id' => 'sync-1',
                'name' => Sync::TASK_NAME_DELETE,
            ]);
            $this->task->resource()->associate($this->vpnEndpoint('vpne-test', false));
            $this->task->save();
        });

        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new UnassignFloatingIP($this->task));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }

    public function testFloatingIpUnassignJobIsDispatched()
    {
        Model::withoutEvents(function () {
            $this->task = new Task([
                'id' => 'sync-1',
                'name' => Sync::TASK_NAME_DELETE,
            ]);
            $this->task->resource()->associate($this->vpnEndpoint());
            $this->task->save();
        });

        Event::fake([JobProcessed::class, Created::class]);

        dispatch(new UnassignFloatingIP($this->task));

        Event::assertNotDispatched(JobFailed::class);

        Event::assertDispatched(Created::class, function ($event) {
            return $event->model->name == 'floating_ip_unassign';
        });
    }

    public function testAwaitFloatingIpSyncIsReleased()
    {
        Model::withoutEvents(function () {
            $this->task = new Task([
                'id' => 'sync-1',
                'name' => Sync::TASK_NAME_DELETE,
            ]);
            $this->task->resource()->associate($this->vpnEndpoint());
            $this->task->save();
        });

        Event::fake([JobFailed::class, Created::class]);

        $assignTask = new Task([
            'id' => 'task-test',
            'completed' => true,
            'name' => 'floating_ip_unassign'
        ]);
        $this->vpnEndpoint()->floatingIp->tasks()->save($assignTask);

        $this->task->data = [
            'floatingip_detach_task_id' => $assignTask->id
        ];
        $this->task->saveQuietly();

        dispatch(new UnassignFloatingIP($this->task));

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testUnassignFloatingIpRegardlessOfState()
    {
        Model::withoutEvents(function () {
            $this->task = new Task([
                'id' => 'sync-1',
                'name' => Sync::TASK_NAME_DELETE,
            ]);
            $this->task->resource()->associate($this->vpnEndpoint());
            $this->task->save();
        });

        Task::withoutEvents(function () {
            $task = new Task([
                'id' => 'sync-2',
                'name' => Sync::TASK_NAME_UPDATE,
                'data' => [],
                'completed' => 0,
                'failure_reason' => 'A reason for the failure',
            ]);
            $task->resource()->associate($this->floatingIp());
            $task->save();
            $this->floatingIp()->refresh();
        });

        $this->assertEquals(Sync::STATUS_FAILED, $this->floatingIp()->sync->status);

        Event::fake([JobProcessed::class]);

        dispatch(new UnassignFloatingIP($this->task));

        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }
}
