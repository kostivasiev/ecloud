<?php

namespace Tests\unit\Jobs\Sync\Instance;

use App\Jobs\Sync\Instance\Update;
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
        Model::withoutEvents(function () {
            $this->task = new Task([
                'id' => 'sync-1',
                'name' => 'test',
                'data' => []
            ]);
            $this->task->resource()->associate($this->instance());
        });

        Bus::fake();
        $job = new Update($this->task);
        $job->handle();

        Bus::assertBatched(function (PendingBatch $batch) {
            return $batch->jobs->count() == 1 && count($batch->jobs->all()[0]) == 23;
        });
    }
}
