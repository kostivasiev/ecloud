<?php

namespace Tests\Unit\Jobs\Network;

use App\Events\V2\Task\Created;
use App\Jobs\Network\CreateManagementNetwork;
use App\Models\V2\Network;
use App\Models\V2\Task;
use App\Support\Sync;
use App\Tasks\Vpc\CreateManagementInfrastructure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CreateManagementNetworkTest extends TestCase
{
    protected Task $task;

    public function setUp(): void
    {
        parent::setUp();

        $this->router()->setAttribute('is_management', true)->saveQuietly();

        Model::withoutEvents(function () {
            $this->task = new Task([
                'id' => 'sync-1',
                'name' => CreateManagementInfrastructure::$name,
                'data' => [
                    'availability_zone_id' => $this->router()->availability_zone_id,
                    'management_router_id' => $this->router()->id,
                ]
            ]);
            $this->task->resource()->associate($this->vpc());
            $this->task->save();
        });
    }

    public function testJobFailsIfNoManagementRouter()
    {
        Event::fake([JobFailed::class, JobProcessed::class, Created::class]);

        $this->task->setAttribute('data', null)->saveQuietly();

        dispatch(new CreateManagementNetwork($this->task));

        $this->task->refresh();

        $this->assertNull($this->task->data);

        Event::assertDispatched(JobFailed::class);

        Event::assertNotDispatched(Created::class, function ($event) {
            return $event->model->resource instanceof Network
                && $event->model->name == Sync::TASK_NAME_UPDATE;
        });

        Event::assertNotDispatched(JobProcessed::class, function ($event) {
            return $event->job->isReleased();
        });
    }

    public function testManagementNetworkIsCreated()
    {
        Event::fake([JobFailed::class, JobProcessed::class, Created::class]);

        dispatch(new CreateManagementNetwork($this->task));

        $this->task->refresh();

        Event::assertDispatched(Created::class, function ($event) {
            return $event->model->resource instanceof Network
                && $event->model->name == Sync::TASK_NAME_UPDATE;
        });

        $event = Event::dispatched(Created::class, function ($event) {
            return $event->model->name == Sync::TASK_NAME_UPDATE;
        })->first()[0];
        $event->model->setAttribute('completed', true)->saveQuietly();

        dispatch(new CreateManagementNetwork($this->task));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });

        $managementNetwork = Network::find($this->task->data['management_network_id']);
        $this->assertNotNull($managementNetwork);
        $this->assertEquals($this->router()->id, $managementNetwork->router_id);
    }

    public function testCreateManagementNetworkExistsSkips()
    {
        $this->network();

        Event::fake([JobFailed::class, JobProcessed::class, Created::class]);

        dispatch(new CreateManagementNetwork($this->task));

        $this->task->refresh();

        Event::assertNotDispatched(JobFailed::class);

        Event::assertNotDispatched(Created::class, function ($event) {
            return $event->model->resource instanceof Network
                && $event->model->name == Sync::TASK_NAME_UPDATE;
        });

        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });

        $this->assertEquals($this->network()->id, $this->task->data['management_network_id']);
    }

    public function testManagementNetworkOnAdvancedRange()
    {
        Event::fake([JobFailed::class, JobProcessed::class, Created::class]);

        $this->vpc()->setAttribute('advanced_networking', true)->saveQuietly();

        dispatch(new CreateManagementNetwork($this->task));

        $this->task->refresh();

        Event::assertDispatched(Created::class, function ($event) {
            return $event->model->resource instanceof Network
                && $event->model->name == Sync::TASK_NAME_UPDATE;
        });

        $event = Event::dispatched(Created::class, function ($event) {
            return $event->model->name == Sync::TASK_NAME_UPDATE;
        })->first()[0];
        $event->model->setAttribute('completed', true)->saveQuietly();

        dispatch(new CreateManagementNetwork($this->task));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });

        $managementNetwork = Network::find($this->task->data['management_network_id']);
        $this->assertNotNull($managementNetwork);
        $this->assertEquals($this->router()->id, $managementNetwork->router_id);
    }

    public function testSubnetAvailability()
    {
        $job = new CreateManagementNetwork($this->task);

        $this->router()->setAttribute('is_management', true)->saveQuietly();
        $subnet = $job->getNextAvailableSubnet('192.168.0.0/17', $this->availabilityZone()->id);
        $this->assertEquals('192.168.4.0/28', $subnet);

        // If 192.168.4.0/28 is in use, then next address should be used 192.168.4.16/28
        $this->network()->setAttribute('subnet', '192.168.4.0/28')->saveQuietly();
        $subnet = $job->getNextAvailableSubnet('192.168.0.0/17', $this->availabilityZone()->id);
        $this->assertEquals('192.168.4.16/28', $subnet);
    }
}
