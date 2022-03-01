<?php

namespace Tests\unit\Jobs\Instance\Undeploy;

use App\Events\V2\Task\Created;
use App\Jobs\Instance\Deploy\AssignFloatingIp;
use App\Jobs\Instance\Undeploy\UnassignFloatingIP;
use App\Models\V2\Task;
use App\Support\Sync;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class UnassignFloatingIpTest extends TestCase
{
    public function testNoFloatingIpSkips()
    {
        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new UnassignFloatingIP($this->instance()));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }

    public function testFloatingIpUnassignJobIsDispatched()
    {
        Event::fake([JobProcessed::class, Created::class]);

        $this->floatingIp()->resource()->associate($this->nic());
        $this->floatingIp()->save();

        $job = \Mockery::mock(UnassignFloatingIP::class, [$this->instance()])->makePartial();
        $job->shouldReceive('awaitTask')->andReturn(true);
        $job->handle();

        Event::assertNotDispatched(JobFailed::class);

        Event::assertDispatched(Created::class, function ($event) {
            return $event->model->name == 'floating_ip_unassign';
        });
    }

    public function testAwaitUnassignFloatingIpTaskTaskFailed()
    {
        Event::fake([JobProcessed::class, Created::class, JobFailed::class]);

        $this->floatingIp()->resource()->associate($this->nic());
        $this->floatingIp()->save();

        $task = new Task([
            'id' => 'task-test',
            'completed' => false,
            'failure_reason' => 'Test Failure',
            'name' => Sync::TASK_NAME_UPDATE,
        ]);
        $this->floatingIp()->tasks()->save($task);

        // Bind and return test ID on creation
        app()->bind(Task::class, function () use ($task) {
            return $task;
        });

        dispatch(new UnassignFloatingIP($this->instance()));

        Event::assertDispatched(JobFailed::class);
    }

    public function testAwaitUnassignFloatingIpTaskTaskSucceeded()
    {
        Event::fake([JobProcessed::class, Created::class, JobFailed::class]);

        $this->floatingIp()->resource()->associate($this->nic());
        $this->floatingIp()->save();

        $task = new Task([
            'id' => 'task-test',
            'completed' => true,
            'name' => Sync::TASK_NAME_UPDATE,
        ]);
        $this->floatingIp()->tasks()->save($task);

        // Bind and return test ID on creation
        app()->bind(Task::class, function () use ($task) {
            return $task;
        });

        $job = new UnassignFloatingIP($this->instance());
        $job->awaitTask($task);

        $this->assertTrue($job->awaitTask($task));
    }
}
