<?php

namespace Tests\Unit\Jobs\FloatingIpResource;

use App\Events\V2\Task\Created;
use App\Jobs\FloatingIpResource\CreateDestinationNat;
use App\Jobs\FloatingIpResource\CreateSourceNat;
use App\Models\V2\FloatingIpResource;
use App\Models\V2\Nat;
use App\Support\Sync;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CreateDestinationNatTest extends TestCase
{
    public function testIpAddressAttachedCreatesNats()
    {
        Event::fake([JobFailed::class, JobProcessed::class, Created::class]);

        // Create the pivot between a fIP and an IP address
        $floatingIpResource = FloatingIpResource::factory()->make();
        $floatingIpResource->floatingIp()->associate($this->floatingIp());
        $floatingIpResource->resource()->associate($this->ip());
        $floatingIpResource->save();

        $task = $this->createSyncUpdateTask($floatingIpResource);

        dispatch(new CreateDestinationNat($task));

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

        dispatch(new CreateDestinationNat($task));

        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });

        $nat = $createDestinationNatTask->model->resource;

        $this->assertEquals($this->floatingIp()->id, $nat->destination_id);
        $this->assertEquals('fip', $nat->destinationable_type);
        $this->assertEquals($this->ip()->id, $nat->translated_id);
        $this->assertEquals('ip', $nat->translatedable_type);
        $this->assertEquals(Nat::ACTION_DNAT, $nat->action);
    }

    public function testOtherResourceAssignedCompletesWithoutNats()
    {
        Event::fake([JobFailed::class, JobProcessed::class, Created::class]);

        $floatingIpResource = FloatingIpResource::factory()->make();
        $floatingIpResource->floatingIp()->associate($this->floatingIp());
        $floatingIpResource->resource()->associate($this->instanceModel());
        $floatingIpResource->save();

        $task = $this->createSyncUpdateTask($floatingIpResource);

        dispatch(new CreateDestinationNat($task));

        Event::assertNotDispatched(JobFailed::class);

        Event::assertNotDispatched(Created::class);
    }

    public function testJobFailedWhenNatSyncFailed()
    {
        Event::fake([JobFailed::class, JobProcessed::class, Created::class]);

        // Create the pivot between a fIP and an IP address
        $floatingIpResource = FloatingIpResource::factory()->make();
        $floatingIpResource->floatingIp()->associate($this->floatingIp());
        $floatingIpResource->resource()->associate($this->ip());
        $floatingIpResource->save();

        $task = $this->createSyncUpdateTask($floatingIpResource);

        dispatch(new CreateDestinationNat($task));

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

        dispatch(new CreateDestinationNat($task));

        Event::assertDispatched(JobFailed::class);
    }
}