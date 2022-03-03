<?php

namespace Tests\Unit\Jobs\OrchestratorBuild;

use App\Events\V2\Task\Created;
use App\Jobs\OrchestratorBuild\AwaitHostGroups;
use App\Models\V2\OrchestratorBuild;
use App\Models\V2\OrchestratorConfig;
use App\Models\V2\Task;
use App\Support\Sync;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class AwaitHostGroupsTest extends TestCase
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

        $this->orchestratorBuild->updateState('hostgroup', 0, $this->hostGroup()->id);

        // Put the VPC sync in-progress
        Model::withoutEvents(function () {
            $task = new Task([
                'id' => 'sync-test',
                'completed' => false,
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $task->resource()->associate($this->hostGroup());
            $task->save();
        });

        dispatch(new AwaitHostGroups($this->orchestratorBuild));

        Event::assertDispatched(JobProcessed::class, function ($event) {
            return $event->job->isReleased();
        });
    }

    public function testFailedResourceFailsJob()
    {
        Event::fake(JobFailed::class);

        $this->orchestratorBuild->updateState('hostgroup', 0, $this->hostGroup()->id);

        Model::withoutEvents(function () {
            $task = new Task([
                'id' => 'sync-test',
                'completed' => false,
                'failure_reason' => 'some failure reason',
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $task->resource()->associate($this->hostGroup());
            $task->save();
        });

        dispatch(new AwaitHostGroups($this->orchestratorBuild));

        Event::assertDispatched(JobFailed::class);
    }

    public function testSuccess()
    {
        Event::fake([JobFailed::class, JobProcessed::class, Created::class]);

        $this->orchestratorBuild->updateState('hostgroup', 0, $this->hostGroup()->id);

        // Put the VPC sync in-progress
        Model::withoutEvents(function () {
            $task = new Task([
                'id' => 'sync-test',
                'completed' => true,
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $task->resource()->associate($this->hostGroup());
            $task->save();
        });


        dispatch(new AwaitHostGroups($this->orchestratorBuild));

        Event::assertNotDispatched(JobFailed::class);

        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }
}