<?php

namespace Tests\unit\Jobs\Tasks\Instance;

use App\Jobs\Tasks\Instance\VolumeAttach;
use App\Models\V2\Task;
use App\Models\V2\Volume;
use Illuminate\Bus\PendingBatch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Bus;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class VolumeAttachTest extends TestCase
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
            $volume = factory(Volume::class)->create([
                'id' => 'test',
                'vpc_id' => $this->vpc()->id,
            ]);

            $this->task = new Task([
                'id' => 'sync-1',
                'name' => 'test',
                'data' => [
                    'volume_id' => $volume->id,
                ]
            ]);
            $this->task->resource()->associate($this->instance());
        });

        Bus::fake();
        $job = new VolumeAttach($this->task);
        $job->handle();

        Bus::assertBatched(function (PendingBatch $batch) {
            return $batch->jobs->count() == 1 && count($batch->jobs->all()[0]) == 2;
        });
    }
}
