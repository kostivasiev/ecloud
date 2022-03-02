<?php

namespace Tests\unit\Jobs\Sync\OrchestratorBuild;

use App\Jobs\Sync\OrchestratorBuild\Update;
use App\Models\V2\OrchestratorBuild;
use App\Models\V2\OrchestratorConfig;
use App\Models\V2\Task;
use Illuminate\Bus\PendingBatch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    private $task;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testJobsBatched()
    {
        Model::withoutEvents(function() {

            $orchestratorConfig = OrchestratorConfig::factory()->create([
                'id' => 'oconf-test'
            ]);

            $orchestratorBuild = OrchestratorBuild::factory()->make();
            $orchestratorBuild->id = 'obuild-test';
            $orchestratorBuild->orchestratorConfig()->associate($orchestratorConfig);
            $orchestratorBuild->save();

            $this->task = new Task([
                'id' => 'sync-1',
                'name' => 'test',
                'data' => []
            ]);
            $this->task->resource()->associate($orchestratorBuild);
        });

        Bus::fake();
        $job = new Update($this->task);
        $job->handle();

        Bus::assertBatched(function (PendingBatch $batch) {
            return $batch->jobs->count() == 1 && count($batch->jobs->all()[0]) == 17;
        });
    }
}
