<?php

namespace Tests\Unit\Jobs\FloatingIpResource;

use App\Events\V2\Task\Created;
use App\Jobs\FloatingIpResource\DeleteDestinationNat;
use App\Models\V2\FloatingIpResource;
use App\Models\V2\Nat;
use App\Models\V2\Task;
use App\Support\Sync;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class DeleteDestinationNatTest extends TestCase
{
    protected Task $task;

    protected FloatingIpResource $floatingIpResource;

    protected function setUp(): void
    {
        parent::setUp();
        // Create the pivot between a fIP and an IP address
        $this->floatingIpResource = FloatingIpResource::factory()->make();
        $this->floatingIpResource->floatingIp()->associate($this->floatingIp());
        $this->floatingIpResource->resource()->associate($this->ip());
        $this->floatingIpResource->save();

        $this->task = $this->createSyncDeleteTask($this->floatingIpResource);
    }

    public function testSuccess()
    {
        Event::fake([Created::class, JobProcessed::class, JobFailed::class]);

        $destinationNat = app()->make(Nat::class);
        $destinationNat->destination()->associate($this->floatingIp());
        $destinationNat->translated()->associate($this->ip());
        $destinationNat->action = Nat::ACTION_DNAT;
        $destinationNat->save();

        dispatch(new DeleteDestinationNat($this->task));

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
        $destinationNat->delete();

        dispatch(new DeleteDestinationNat($this->task));

        Event::assertNotDispatched(JobFailed::class);

        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }

    public function testJobCompletesWithNoNats()
    {
        Event::fake([JobFailed::class, JobProcessed::class, ]);

        dispatch(new DeleteDestinationNat($this->task));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }
}