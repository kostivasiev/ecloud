<?php

namespace Tests\unit\Jobs\Tasks\FloatingIp;

use App\Jobs\Tasks\FloatingIp\Unassign;
use App\Models\V2\Task;
use Illuminate\Bus\PendingBatch;
use Illuminate\Support\Facades\Bus;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class UnassignTest extends TestCase
{
    use DatabaseMigrations;

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
        $job = new Unassign($this->task);
        $job->handle();

        Bus::assertBatched(function (PendingBatch $batch) {
            return $batch->jobs->count() == 1 && count($batch->jobs->all()[0]) == 2;
        });
    }
}
