<?php

namespace Tests\Unit\Jobs\Tasks\Instance;

use App\Jobs\Tasks\Instance\MigratePublic;
use App\Models\V2\Task;
use Illuminate\Bus\PendingBatch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class MigratePublicTest extends TestCase
{
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
            ]);
            $this->task->resource()->associate($this->instanceModel());
        });

        Bus::fake();
        $job = new MigratePublic($this->task);
        $job->handle();

        Bus::assertBatched(function (PendingBatch $batch) {
            return $batch->jobs->count() == 1 && count($batch->jobs->all()[0]) == 1;
        });

        // Check that host_group has been disassociated
        $this->assertNull($this->instanceModel()->host_group_id);
    }
}
