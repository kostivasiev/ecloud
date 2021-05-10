<?php

namespace Tests\unit\Jobs\Tasks\Volume;

use App\Jobs\Sync\Router\Update;
use App\Jobs\Tasks\Volume\VolumeAttach;
use App\Jobs\Tasks\Volume\VolumeDetach;
use App\Models\V2\Task;
use App\Models\V2\Volume;
use App\Support\Sync;
use Illuminate\Bus\PendingBatch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Bus;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class VolumeDetachTest extends TestCase
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
                    'instance_id' => $this->instance()->id,
                ]
            ]);
            $this->task->resource()->associate($volume);
        });

        Bus::fake();
        $job = new VolumeDetach($this->task);
        $job->handle();

        Bus::assertBatched(function (PendingBatch $batch) {
            return $batch->jobs->count() == 1 && count($batch->jobs->all()[0]) == 1;
        });
    }
}
