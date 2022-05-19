<?php

namespace Tests\Unit\Jobs\FloatingIp;

use App\Events\V2\Task\Created;
use App\Jobs\FloatingIp\CreateFloatingIpResource;
use App\Jobs\FloatingIp\DeleteFloatingIpResource;
use App\Jobs\Tasks\FloatingIp\Unassign;
use App\Models\V2\FloatingIpResource;
use App\Models\V2\Task;
use App\Support\Sync;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class DeleteFloatingIpResourceTest extends TestCase
{
    protected Task $task;
    protected FloatingIpResource $floatingIpResource;

    protected function setUp(): void
    {
        parent::setUp();

        Task::withoutEvents(function () {
            $this->task = new Task([
                'id' => 'test-task',
                'name' => Unassign::$name,
                'job' => Unassign::class,
                'data' => [
                    'resource_id' => $this->ipAddress()->id,
                ]
            ]);
            $this->task->resource()->associate($this->floatingIp());
            $this->task->save();
        });

        $this->floatingIpResource = $this->assignFloatingIp($this->floatingIp(), $this->ipAddress());
    }

    public function testSuccess()
    {
        Event::fake([Created::class, JobProcessed::class, JobFailed::class]);

        dispatch(new DeleteFloatingIpResource($this->task));

        Event::assertDispatched(Created::class, function ($event) {
            return $event->model->name == Sync::TASK_NAME_DELETE;
        });

        Event::assertDispatched(JobProcessed::class, function ($event) {
            return $event->job->isReleased();
        });

        // Mark the task as completed
        $DeleteResourceTask = Event::dispatched(Created::class, function ($event) {
            return $event->model->name == Sync::TASK_NAME_DELETE;
        })->first()[0];

        $DeleteResourceTask->model->setAttribute('completed', true)->saveQuietly();

        dispatch(new CreateFloatingIpResource($this->task));

        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testResourceNotFoundSuccess()
    {
        Event::fake([Created::class, JobProcessed::class, JobFailed::class]);

        $this->floatingIpResource->delete();

        dispatch(new DeleteFloatingIpResource($this->task));

        Event::assertNotDispatched(Created::class);

        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });

        Event::assertNotDispatched(JobFailed::class);
    }
}