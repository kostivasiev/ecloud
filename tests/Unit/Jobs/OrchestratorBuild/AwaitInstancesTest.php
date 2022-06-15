<?php

namespace Tests\Unit\Jobs\OrchestratorBuild;

use App\Events\V2\Task\Created;
use App\Jobs\OrchestratorBuild\AwaitInstances;
use App\Models\V2\Instance;
use App\Models\V2\OrchestratorBuild;
use App\Models\V2\OrchestratorConfig;
use App\Models\V2\Task;
use App\Support\Sync;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class AwaitInstancesTest extends TestCase
{
    protected OrchestratorConfig $orchestratorConfig;

    protected OrchestratorBuild $orchestratorBuild;

    public function setUp(): void
    {
        parent::setUp();
        $this->availabilityZone();

        $this->orchestratorConfig = OrchestratorConfig::factory()->create();

        $this->orchestratorBuild = OrchestratorBuild::factory()->make();
        $this->orchestratorBuild->orchestratorConfig()->associate($this->orchestratorConfig);
        $this->orchestratorBuild->save();
    }

    public function testResourceInProgressReleasedBackIntoQueue()
    {
        Event::fake([JobFailed::class, JobProcessed::class]);

        $this->orchestratorBuild->updateState('instance', 0, [$this->instanceModel()->id]);

        // Put the sync in-progress
        Model::withoutEvents(function () {
            $task = new Task([
                'id' => 'sync-test',
                'completed' => false,
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $task->resource()->associate($this->instanceModel());
            $task->save();
        });

        dispatch(new AwaitInstances($this->orchestratorBuild));

        Event::assertDispatched(JobProcessed::class, function ($event) {
            return $event->job->isReleased();
        });
    }

    public function testFailedResourceFailsJob()
    {
        Event::fake(JobFailed::class);

        $this->orchestratorBuild->updateState('instance', 0, [$this->instanceModel()->id]);

        Model::withoutEvents(function () {
            $task = new Task([
                'id' => 'sync-test',
                'completed' => false,
                'failure_reason' => 'some failure reason',
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $task->resource()->associate($this->instanceModel());
            $task->save();
        });

        dispatch(new AwaitInstances($this->orchestratorBuild));

        Event::assertDispatched(JobFailed::class);
    }

    public function testSuccess()
    {
        Event::fake([JobFailed::class, JobProcessed::class, Created::class]);

        $this->orchestratorBuild->updateState('instance', 0, [$this->instanceModel()->id]);

        Model::withoutEvents(function () {
            $task = new Task([
                'id' => 'sync-test',
                'completed' => true,
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $task->resource()->associate($this->instanceModel());
            $task->save();
        });


        dispatch(new AwaitInstances($this->orchestratorBuild));

        Event::assertNotDispatched(JobFailed::class);

        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }

    public function testMultipleSuccess()
    {
        Event::fake([JobFailed::class, JobProcessed::class, Created::class]);

        $this->orchestratorBuild->updateState('instance', 0, [
            $this->createInstanceAndTask('i-aaaaaaaa')->id,
            $this->createInstanceAndTask('i-bbbbbbbb')->id,
        ]);
        $this->orchestratorBuild->updateState('instance', 1, [
            $this->createInstanceAndTask('i-cccccccc')->id,
        ]);
        $this->orchestratorBuild->updateState('instance', 2, [
            $this->createInstanceAndTask('i-dddddddd')->id,
            $this->createInstanceAndTask('i-eeeeeeee')->id,
        ]);

        dispatch(new AwaitInstances($this->orchestratorBuild));

        Event::assertNotDispatched(JobFailed::class);

        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }

    private function createInstanceAndTask(string $id): Instance
    {
        return Instance::withoutEvents(function () use ($id) {
            $instance = Instance::factory()->create([
                'id' => $id,
                'vpc_id' => $this->vpc()->id,
                'name' => 'Test Instance ' . uniqid(),
                'image_id' => $this->image()->id,
                'vcpu_cores' => 1,
                'ram_capacity' => 1024,
                'availability_zone_id' => $this->availabilityZone()->id,
                'deploy_data' => [
                    'network_id' => $this->network()->id,
                    'volume_capacity' => 20,
                    'volume_iops' => 300,
                    'requires_floating_ip' => false,
                ]
            ]);

            $task = new Task([
                'id' => 'sync-test-' . $id,
                'completed' => true,
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $task->resource()->associate($instance);
            $task->save();

            return $instance;
        });
    }
}