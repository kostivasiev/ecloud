<?php

namespace Tests\unit\Jobs\Sync\FloatingIp;

use App\Jobs\Sync\FloatingIp\Delete;
use App\Models\V2\Task;
use App\Support\Sync;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class DeleteTest extends TestCase
{
    private $task;

    public function testJobsBatched()
    {
        Model::withoutEvents(function() {
            $this->task = new Task([
                'id' => 'sync-1',
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $this->task->resource()->associate($this->floatingIp());
        });

        Bus::fake();
        $job = new Delete($this->task);
        $job->handle();

        $this->task->refresh();

        $this->assertNotNull($this->floatingIp()->deleted_at);
        $this->assertTrue($this->task->completed);
    }
}
