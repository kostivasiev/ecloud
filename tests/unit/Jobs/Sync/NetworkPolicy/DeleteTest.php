<?php

namespace Tests\unit\Jobs\Sync\NetworkPolicy;

use App\Jobs\Sync\NetworkPolicy\Delete;
use App\Models\V2\Task;
use App\Support\Sync;
use Illuminate\Bus\PendingBatch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Bus;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class DeleteTest extends TestCase
{
    use DatabaseMigrations;

    private $task;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testJobsBatched()
    {
        Model::withoutEvents(function() {
            $this->task = new Task([
                'id' => 'task-1',
                'name' => Sync::TASK_NAME_DELETE,
            ]);
            $this->task->resource()->associate($this->networkPolicy());
        });

        Bus::fake();
        $job = new Delete($this->task);
        $job->handle();

        Bus::assertBatched(function (PendingBatch $batch) {
            return $batch->jobs->count() > 0;
        });
    }
}
