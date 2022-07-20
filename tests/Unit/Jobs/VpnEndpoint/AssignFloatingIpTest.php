<?php

namespace Tests\Unit\Jobs\VpnEndpoint;

use App\Events\V2\Task\Created;
use App\Jobs\Tasks\FloatingIp\Assign;
use App\Jobs\VpnEndpoint\AssignFloatingIP;
use App\Models\V2\FloatingIp;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Tests\Mocks\Resources\VpnEndpointMock;
use Tests\Mocks\Resources\VpnServiceMock;
use Tests\TestCase;

class AssignFloatingIpTest extends TestCase
{
    use VpnServiceMock, VpnEndpointMock;

    public function testSuccess()
    {
        Event::fake([JobFailed::class, JobProcessed::class, Created::class]);

        $task = $this->createSyncUpdateTask($this->vpnEndpoint());
        $task->setAttribute('data', ['floating_ip_id' => $this->floatingIp()->id])->saveQuietly();

        dispatch(new AssignFloatingIp($task));

        Event::assertDispatched(Created::class, function ($event) {
            return $event->model->name == Assign::TASK_NAME
                && $event->model->resource instanceof FloatingIp;
        });

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return $event->job->isReleased();
        });

        // Mark the task as completed
        $assignTask = Event::dispatched(Created::class, function ($event) {
            return $event->model->name == Assign::TASK_NAME;
        })->first()[0];

        $assignTask->model->setAttribute('completed', true)->saveQuietly();

        dispatch(new AssignFloatingIp($task));

        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }

    public function testFloatingIpAlreadyAssignedSkips()
    {
        Event::fake([JobFailed::class, JobProcessed::class, Created::class]);

        $task = $this->createSyncUpdateTask($this->vpnEndpoint());

        $task->setAttribute('data', ['floating_ip_id' => $this->floatingIp()->id])->saveQuietly();

        $this->assignFloatingIp($this->floatingIp(), $this->vpnEndpoint());

        dispatch(new AssignFloatingIp($task));

        Event::assertNotDispatched(Created::class);

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }

    public function testFailedToLoadFipFails()
    {
        Event::fake([JobFailed::class, JobProcessed::class, Created::class]);

        $task = $this->createSyncUpdateTask($this->vpnEndpoint());

        $task->setAttribute('data', ['floating_ip_id' => 'I DONT EXIST'])->saveQuietly();

        dispatch(new AssignFloatingIp($task));

        Event::assertNotDispatched(Created::class);

        Event::assertDispatched(JobFailed::class);
    }
}