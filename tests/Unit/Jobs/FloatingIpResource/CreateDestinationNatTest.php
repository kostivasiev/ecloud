<?php

namespace Tests\Unit\Jobs\FloatingIpResource;

use App\Events\V2\Task\Created;
use App\Jobs\FloatingIpResource\CreateDestinationNat;
use App\Models\V2\FloatingIpResource;
use App\Models\V2\Nat;
use App\Models\V2\Task;
use App\Support\Sync;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CreateDestinationNatTest extends TestCase
{
    protected FloatingIpResource $floatingIpResource;

    protected Task $task;

    public function setUp(): void
    {
        parent::setUp();
        $this->floatingIpResource = $this->assignFloatingIp($this->floatingIp(), $this->ipAddress());

        $this->task = $this->createSyncUpdateTask($this->floatingIpResource);
    }

    public function testIpAddressAttachedCreatesNats()
    {
        Event::fake([JobFailed::class, JobProcessed::class, Created::class]);

        dispatch(new CreateDestinationNat($this->task));

        Event::assertNotDispatched(JobFailed::class);

        Event::assertDispatched(Created::class, function ($event) {
            return $event->model->name == 'sync_update'
                && $event->model->resource_type == Nat::class
                && $event->model->resource->action == Nat::ACTION_DNAT;
        });

        Event::assertDispatched(JobProcessed::class, function ($event) {
            return $event->job->isReleased();
        });

        // Mark the task as completed
        $createDestinationNatTask = Event::dispatched(Created::class, function ($event) {
            return $event->model->name == Sync::TASK_NAME_UPDATE;
        })->first()[0];

        $createDestinationNatTask->model->setAttribute('completed', true)->saveQuietly();

        dispatch(new CreateDestinationNat($this->task));

        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });

        $nat = $createDestinationNatTask->model->resource;

        $this->assertEquals($this->floatingIp()->id, $nat->destination_id);
        $this->assertEquals('fip', $nat->destinationable_type);
        $this->assertEquals($this->ipAddress()->id, $nat->translated_id);
        $this->assertEquals('ip', $nat->translatedable_type);
        $this->assertEquals(Nat::ACTION_DNAT, $nat->action);
    }

    public function testOtherResourceAssignedCompletesWithoutNats()
    {
        Event::fake([JobFailed::class, JobProcessed::class, Created::class]);

        $this->floatingIpResource->resource()->associate($this->instanceModel());
        $this->floatingIpResource->save();

        dispatch(new CreateDestinationNat($this->task));

        Event::assertNotDispatched(JobFailed::class);

        Event::assertNotDispatched(Created::class);
    }

    public function testJobFailedWhenNatSyncFailed()
    {
        Event::fake([JobFailed::class, JobProcessed::class, Created::class]);

        dispatch(new CreateDestinationNat($this->task));

        Event::assertNotDispatched(JobFailed::class);

        Event::assertDispatched(Created::class, function ($event) {
            return $event->model->name == 'sync_update'
                && $event->model->resource_type == Nat::class
                && $event->model->resource->action == Nat::ACTION_DNAT;
        });

        Event::assertDispatched(JobProcessed::class, function ($event) {
            return $event->job->isReleased();
        });

        // Mark the task as completed
        $createSourceNatTask = Event::dispatched(Created::class, function ($event) {
            return $event->model->name == Sync::TASK_NAME_UPDATE;
        })->first()[0];

        $createSourceNatTask->model
            ->setAttribute('completed', false)
            ->setAttribute('failure_reason', 'test')
            ->saveQuietly();

        dispatch(new CreateDestinationNat($this->task));

        Event::assertDispatched(JobFailed::class);
    }
}