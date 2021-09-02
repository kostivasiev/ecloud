<?php

namespace Tests\unit\Jobs\VpnEndpoint;

use App\Events\V2\Task\Created;
use App\Jobs\VpnEndpoint\UnassignFloatingIP;
use App\Models\V2\FloatingIp;
use App\Models\V2\Task;
use App\Support\Sync;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Tests\Mocks\Resources\VpnEndpointMock;
use Tests\Mocks\Resources\VpnServiceMock;
use Tests\TestCase;

class UnassignFloatingIpTest extends TestCase
{
    use VpnServiceMock, VpnEndpointMock;

    public function testNoFloatingIpAssignedSucceeds()
    {
        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new UnassignFloatingIP($this->vpnEndpoint('vpne-test', false)));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }

    public function testFloatingIpUnassignJobIsDispatched()
    {
        Event::fake([JobProcessed::class, Created::class]);

        dispatch(new UnassignFloatingIP($this->vpnEndpoint()));

        Event::assertNotDispatched(JobFailed::class);

        Event::assertDispatched(Created::class, function ($event) {
            return $event->model->name == 'floating_ip_unassign';
        });
    }

    public function testAwaitFloatingIpSyncIsReleased()
    {
        Event::fake([JobProcessed::class, Created::class]);

        $this->vpnEndpoint();

        $task = new Task([
            'id' => 'task-test',
            'completed' => false,
            'name' => 'floating_ip_unassign',
        ]);
        $this->vpnEndpoint()->floatingIp->tasks()->save($task);

        dispatch(new UnassignFloatingIP($this->vpnEndpoint()));

        Event::assertDispatched(JobProcessed::class, function ($event) {
            return $event->job->isReleased();
        });
    }

    public function testUnassignFloatingIpRegardlessOfState()
    {
        Task::withoutEvents(function () {
            $task = new Task([
                'id' => 'sync-1',
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

        (new UnassignFloatingIP($this->vpnEndpoint()))->handle();

        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }
}
