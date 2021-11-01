<?php

namespace Tests\unit\Jobs\LoadBalancer;

use App\Jobs\Sync\LoadBalancer\Update;
use App\Models\V2\LoadBalancer;
use App\Models\V2\LoadBalancerSpecification;
use App\Models\V2\OrchestratorBuild;
use App\Models\V2\OrchestratorConfig;
use App\Models\V2\Task;
use Illuminate\Bus\PendingBatch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Bus;
use Tests\Mocks\Resources\LoadBalancerMock;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    use LoadBalancerMock;

    private $task;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testJobsBatched()
    {
        Model::withoutEvents(function() {
            $this->task = new Task([
                'id' => 'sync-1',
                'name' => 'test',
                'data' => []
            ]);
            $this->task->resource()->associate($this->loadBalancer());
        });

        Bus::fake();
        $job = new Update($this->task);
        $job->handle();

        Bus::assertBatched(function (PendingBatch $batch) {
            return $batch->jobs->count() == 1 && count($batch->jobs->all()[0]) == 1;
        });
    }
}
