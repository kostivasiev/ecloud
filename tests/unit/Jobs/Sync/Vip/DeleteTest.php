<?php

namespace Tests\unit\Jobs\Sync\Vip;

use App\Jobs\Sync\Vip\Delete;
use App\Models\V2\Task;
use App\Support\Sync;
use Illuminate\Bus\PendingBatch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class DeleteTest extends TestCase
{
    private $task;

    public function testJobsBatched()
    {
        $this->markTestSkipped();

        Model::withoutEvents(function() {
            $this->task = new Task([
                'id' => 'task-1',
                'name' => Sync::TASK_NAME_DELETE,
            ]);
            $this->task->resource()->associate($this->vip());
        });

        Bus::fake();
        $job = new Delete($this->task);
        $job->handle();

        Bus::assertBatched(function (PendingBatch $batch) {
            return $batch->jobs->count() == 1 && count($batch->jobs->all()[0]) == 5;
        });
    }
}
