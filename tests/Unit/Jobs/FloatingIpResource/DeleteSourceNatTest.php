<?php

namespace Tests\Unit\Jobs\FloatingIpResource;

use App\Events\V2\Task\Created;
use App\Jobs\FloatingIpResource\DeleteSourceNat;
use App\Models\V2\FloatingIpResource;
use App\Models\V2\Nat;
use App\Models\V2\Task;
use App\Support\Sync;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class DeleteSourceNatTest extends TestCase
{
    protected Task $task;

    protected FloatingIpResource $floatingIpResource;

    protected function setUp(): void
    {
        parent::setUp();
        $this->floatingIpResource = $this->assignFloatingIp($this->floatingIp(), $this->ipAddress());

        $this->task = $this->createSyncDeleteTask($this->floatingIpResource);
    }

    public function testSuccess()
    {
        Event::fake([Created::class, JobProcessed::class, JobFailed::class]);

        $sourceNat = app()->make(Nat::class);
        $sourceNat->source()->associate($this->ipAddress());
        $sourceNat->translated()->associate($this->floatingIp());
        $sourceNat->action = NAT::ACTION_SNAT;
        $sourceNat->save();

        dispatch(new DeleteSourceNat($this->task));

        Event::assertDispatched(Created::class, function ($event) {
            return $event->model->name == Sync::TASK_NAME_DELETE;
        });

        Event::assertNotDispatched(JobFailed::class);

        Event::assertDispatched(JobProcessed::class, function ($event) {
            return $event->job->isReleased();
        });

        // Mark the task as completed
        $DeleteResourceTask = Event::dispatched(Created::class, function ($event) {
            return $event->model->name == Sync::TASK_NAME_DELETE;
        })->first()[0];

        $DeleteResourceTask->model->setAttribute('completed', true)->saveQuietly();
        $sourceNat->delete();

        dispatch(new DeleteSourceNat($this->task));

        Event::assertNotDispatched(JobFailed::class);

        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }

    public function testJobCompletesWithNoNats()
    {
        Event::fake([JobFailed::class, JobProcessed::class, ]);

        dispatch(new DeleteSourceNat($this->task));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }
}