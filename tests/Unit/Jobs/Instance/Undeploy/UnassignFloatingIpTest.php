<?php

namespace Tests\Unit\Jobs\Instance\Undeploy;

use App\Events\V2\Task\Created;
use App\Jobs\Instance\Undeploy\UnassignFloatingIP;
use App\Jobs\Tasks\FloatingIp\Unassign;
use App\Models\V2\IpAddress;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class UnassignFloatingIpTest extends TestCase
{
    public function testNoFloatingIpSkips()
    {
        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new UnassignFloatingIP($this->createSyncDeleteTask($this->instanceModel())));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }

    public function testFloatingIpAssignedToIpAddressJobIsDispatched()
    {
        Event::fake([JobProcessed::class, Created::class]);

        $this->nic()->ipAddresses()->save($this->ipAddress());
        $this->assignFloatingIp($this->floatingIp(), $this->ipAddress());

        dispatch(new UnassignFloatingIP($this->createSyncDeleteTask($this->instanceModel())));

        Event::assertNotDispatched(JobFailed::class);

        Event::assertDispatched(Created::class, function ($event) {
            return $event->model->name == 'floating_ip_unassign';
        });
    }

    public function testAwaitUnassignFloatingIpTaskTaskFailed()
    {
        Event::fake([JobProcessed::class, Created::class, JobFailed::class]);

        $ipAddress = IpAddress::factory()->create();
        $ipAddress->nics()->sync($this->nic());

        $this->assignFloatingIp($this->floatingIp(), $ipAddress);

        $task = $this->createSyncDeleteTask($this->instanceModel());

        dispatch(new UnassignFloatingIP($task));

        // Mark the delete sync task as completed
        $unassignTask = Event::dispatched(Created::class, function ($event) {
            return $event->model->name == Unassign::TASK_NAME;
        })->first()[0];

        $unassignTask->model
            ->setAttribute('completed', false)
            ->setAttribute('failure_reason', 'test')
            ->saveQuietly();

        dispatch(new UnassignFloatingIP($task));

        Event::assertDispatched(JobFailed::class);
    }

    public function testSuccess()
    {
        Event::fake([JobProcessed::class, Created::class, JobFailed::class]);

        $ipAddress = IpAddress::factory()->create();
        $ipAddress->nics()->sync($this->nic());

        $this->assignFloatingIp($this->floatingIp(), $ipAddress);

        $task = $this->createSyncDeleteTask($this->instanceModel());

        dispatch(new UnassignFloatingIP($task));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return $event->job->isReleased();
        });

        // Mark the delete task as completed
        $unassignTask = Event::dispatched(Created::class, function ($event) {
            return $event->model->name == Unassign::TASK_NAME;
        })->first()[0];

        $unassignTask->model->setAttribute('completed', true)->saveQuietly();

        dispatch(new UnassignFloatingIP($task));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }
}
