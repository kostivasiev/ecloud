<?php
namespace Tests\unit\Jobs\OrchestratorBuild;

use App\Events\V2\Task\Created;
use App\Jobs\OrchestratorBuild\AwaitVpcs;
use App\Models\V2\OrchestratorBuild;
use App\Models\V2\OrchestratorConfig;
use App\Models\V2\Task;
use App\Support\Sync;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class AwaitVpcsTest extends TestCase
{
    protected OrchestratorConfig $orchestratorConfig;

    protected OrchestratorBuild $orchestratorBuild;

    public function setUp(): void
    {
        parent::setUp();
        $this->availabilityZone();

        $this->orchestratorConfig = factory(OrchestratorConfig::class)->create();

        $this->orchestratorBuild = factory(OrchestratorBuild::class)->make();
        $this->orchestratorBuild->orchestratorConfig()->associate($this->orchestratorConfig);
        $this->orchestratorBuild->save();
    }

    public function testResourceInProgressReleasedBackIntoQueue()
    {
        Event::fake([JobFailed::class, JobProcessed::class]);

        $this->orchestratorBuild->updateState('vpc', 0, $this->vpc()->id);

        // Put the VPC sync in-progress
        Model::withoutEvents(function () {
            $task = new Task([
                'id' => 'sync-test',
                'completed' => false,
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $task->resource()->associate($this->vpc());
            $task->save();
        });

        dispatch(new AwaitVpcs($this->orchestratorBuild));

        Event::assertDispatched(JobProcessed::class, function ($event) {
            return $event->job->isReleased();
        });
    }

    function testFailedResourceFailsJob()
    {
        Event::fake(JobFailed::class);

        $this->orchestratorBuild->updateState('vpc', 0, $this->vpc()->id);

        Model::withoutEvents(function () {
            $task = new Task([
                'id' => 'sync-test',
                'completed' => false,
                'failure_reason' => 'some failure reason',
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $task->resource()->associate($this->vpc());
            $task->save();
        });

        dispatch(new AwaitVpcs($this->orchestratorBuild));

        Event::assertDispatched(JobFailed::class);
    }

    public function testSuccess()
    {
        Event::fake([JobFailed::class, JobProcessed::class, Created::class]);

        $this->orchestratorBuild->updateState('vpc', 0, $this->vpc()->id);

        // Put the VPC sync in-progress
        Model::withoutEvents(function () {
            $task = new Task([
                'id' => 'sync-test',
                'completed' => true,
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $task->resource()->associate($this->vpc());
            $task->save();
        });


        dispatch(new AwaitVpcs($this->orchestratorBuild));

        Event::assertNotDispatched(JobFailed::class);

        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }
}