<?php

namespace Tests\unit\Jobs\Tasks\FloatingIp;

use App\Jobs\Tasks\FloatingIp\Assign;
use App\Models\V2\Task;
use Illuminate\Bus\PendingBatch;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class AssignTest extends TestCase
{

    private $task;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testJobsBatched()
    {
        $this->task = new Task([
            'id' => 'sync-1',
            'name' => 'test',
            'data' => [
                'resource_id' => $this->nic()->id,
            ]
        ]);
        $this->task->resource()->associate($this->floatingIp());

        Bus::fake();
        $job = new Assign($this->task);
        $job->handle();

        Bus::assertBatched(function (PendingBatch $batch) {
            return $batch->jobs->count() == 1 && count($batch->jobs->all()[0]) == 2;
        });
    }
}
