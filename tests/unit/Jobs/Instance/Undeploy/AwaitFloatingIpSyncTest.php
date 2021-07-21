<?php

namespace Tests\unit\Jobs\Instance\Undeploy;

use App\Jobs\Instance\Undeploy\AwaitFloatingIpSync;
use App\Jobs\Instance\Undeploy\AwaitNicRemoval;
use App\Models\V2\Instance;
use App\Models\V2\Nic;
use App\Models\V2\Task;
use App\Support\Sync;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class AwaitFloatingIpSyncTest extends TestCase
{
    public function testJobSucceedsWithNoFloatingIps()
    {
        $this->nic();
        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new AwaitFloatingIpSync($this->instance()));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }

    public function testJobFailedWhenFloatingIpSyncFailed()
    {
        $this->floatingIp()->resource()->associate($this->nic());
        $this->floatingIp()->save();

        $task = new Task([
            'id' => 'task-1',
            'completed' => false,
            'failure_reason' => 'test',
            'name' => Sync::TASK_NAME_DELETE,
        ]);
        $task->resource()->associate($this->floatingIp());
        $task->save();

        Event::fake([JobFailed::class]);

        dispatch(new AwaitFloatingIpSync($this->instance()));

        Event::assertDispatched(JobFailed::class);
    }

    public function testJobReleasedWhenFloatingIpSyncInProgress()
    {
        $this->floatingIp()->resource()->associate($this->nic());
        $this->floatingIp()->save();

        $task = new Task([
            'id' => 'task-1',
            'completed' => false,
            'name' => Sync::TASK_NAME_DELETE,
        ]);
        $task->resource()->associate($this->floatingIp());
        $task->save();

        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new AwaitFloatingIpSync($this->instance()));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return $event->job->isReleased();
        });
    }
}
