<?php

namespace Tests\Unit\Jobs\OrchestratorBuild;

use App\Events\V2\Task\Created;
use App\Jobs\OrchestratorBuild\AwaitVolumes;
use App\Models\V2\OrchestratorBuild;
use App\Models\V2\OrchestratorConfig;
use App\Models\V2\Task;
use App\Models\V2\Volume;
use App\Support\Sync;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class AwaitVolumesTest extends TestCase
{
    protected OrchestratorConfig $orchestratorConfig;
    protected OrchestratorBuild $orchestratorBuild;
    protected Volume $volume;

    public function setUp(): void
    {
        parent::setUp();
        $this->availabilityZone();

        $this->orchestratorConfig = OrchestratorConfig::factory()->create();

        $this->orchestratorBuild = OrchestratorBuild::factory()->make();
        $this->orchestratorBuild->orchestratorConfig()->associate($this->orchestratorConfig);
        $this->orchestratorBuild->save();
        $this->volume = Volume::withoutEvents(function () {
            return Volume::factory()->create([
                'id' => 'vol-test',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
                'vmware_uuid' => 'd7a86079-6b02-4373-b2ca-6ec24fef2f1c',
            ]);
        });
    }

    public function testResourceInProgressReleasedBackIntoQueue()
    {
        Event::fake([JobFailed::class, JobProcessed::class]);

        $this->orchestratorBuild->updateState('volume', 0, $this->volume->id);

        // Put the sync in-progress
        Model::withoutEvents(function () {
            $task = new Task([
                'id' => 'sync-test',
                'completed' => false,
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $task->resource()->associate($this->volume);
            $task->save();
        });

        dispatch(new AwaitVolumes($this->orchestratorBuild));

        Event::assertDispatched(JobProcessed::class, function ($event) {
            return $event->job->isReleased();
        });
    }

    public function testFailedResourceFailsJob()
    {
        Event::fake(JobFailed::class);

        $this->orchestratorBuild->updateState('volume', 0, $this->volume->id);

        Model::withoutEvents(function () {
            $task = new Task([
                'id' => 'sync-test',
                'completed' => false,
                'failure_reason' => 'some failure reason',
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $task->resource()->associate($this->volume);
            $task->save();
        });

        dispatch(new AwaitVolumes($this->orchestratorBuild));

        Event::assertDispatched(JobFailed::class);
    }

    public function testSuccess()
    {
        Event::fake([JobFailed::class, JobProcessed::class, Created::class]);

        $this->orchestratorBuild->updateState('volume', 0, $this->volume->id);

        Model::withoutEvents(function () {
            $task = new Task([
                'id' => 'sync-test',
                'completed' => true,
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $task->resource()->associate($this->volume);
            $task->save();
        });


        dispatch(new AwaitVolumes($this->orchestratorBuild));

        Event::assertNotDispatched(JobFailed::class);

        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }
}
