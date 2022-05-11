<?php

namespace Tests\Unit\Jobs\Sync\Image;

use App\Jobs\Sync\Image\Delete;
use App\Models\V2\Task;
use App\Support\Sync;
use Illuminate\Bus\PendingBatch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class DeleteTest extends TestCase
{
    private $sync;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testJobsBatched()
    {
        Model::withoutEvents(function() {
            $this->sync = new Task([
                'id' => 'sync-1',
                'name' => Sync::TASK_NAME_DELETE,
            ]);
            $this->sync->resource()->associate($this->image());
        });

        Bus::fake();
        $job = new Delete($this->sync);
        $job->handle();

        Bus::assertBatched(function (PendingBatch $batch) {
            return $batch->jobs->count() == 1 && count($batch->jobs->all()[0]) == 3;
        });
    }
}
