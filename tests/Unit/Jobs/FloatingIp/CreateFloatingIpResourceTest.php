<?php

namespace Tests\Unit\Jobs\FloatingIp;

use App\Jobs\FloatingIp\CreateFloatingIpResource;
use App\Jobs\Tasks\FloatingIp\Assign;
use App\Models\V2\IpAddress;
use App\Support\Sync;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use App\Events\V2\Task\Created;
use App\Models\V2\Task;
use Tests\TestCase;

class CreateFloatingIpResourceTest extends TestCase
{
    protected Task $task;

    protected function setUp(): void
    {
        parent::setUp();

        Task::withoutEvents(function () {
            $this->task = new Task([
                'id' => 'test-task',
                'name' => Assign::$name,
                'job' => Assign::class,
                'data' => [
                    'resource_id' => $this->ipAddress()->id,
                ]
            ]);
            $this->task->resource()->associate($this->floatingIp());
            $this->task->save();
        });
    }

    public function testSuccess()
    {
        Event::fake([Created::class, JobProcessed::class]);

        dispatch(new CreateFloatingIpResource($this->task));

        Event::assertDispatched(Created::class, function ($event) {
            return $event->model->name == Sync::TASK_NAME_UPDATE;
        });

        Event::assertDispatched(JobProcessed::class, function ($event) {
            return $event->job->isReleased();
        });

        // Mark the task as completed
        $createFloatingIpResourceTask = Event::dispatched(Created::class, function ($event) {
            return $event->model->name == Sync::TASK_NAME_UPDATE;
        })->first()[0];

        $createFloatingIpResourceTask->model->setAttribute('completed', true)->saveQuietly();

        dispatch(new CreateFloatingIpResource($this->task));

        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });

        $floatingIpResource = $createFloatingIpResourceTask->model->resource;

        $this->assertEquals($this->floatingIp()->id, $floatingIpResource->floatingIp->id);

        $this->assertEquals($this->ipAddress()->id, $floatingIpResource->resource_id);
        $this->assertEquals('ip', $floatingIpResource->resource_type);

        $this->assertInstanceOf(IpAddress::class, $floatingIpResource->resource);
        $this->assertEquals($this->ipAddress()->id, $floatingIpResource->resource->id);
    }

    public function testUnableToLoadResourceFails()
    {
        Event::fake([Created::class, JobProcessed::class, JobFailed::class]);

        $this->task->setAttribute('data', ['resource_id' => 'I DONT EXIST'])->saveQuietly();

        dispatch(new CreateFloatingIpResource($this->task));

        Event::assertDispatched(JobFailed::class);

        Event::assertNotDispatched(Created::class);
    }
}