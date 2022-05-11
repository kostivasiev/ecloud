<?php

namespace Tests\Unit\Jobs\Sync\Nat;

use App\Jobs\Sync\Nat\Update;
use App\Models\V2\Nat;
use App\Models\V2\Task;
use App\Support\Sync;
use Illuminate\Bus\PendingBatch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    private $sync;
    private $nat;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testJobsBatched()
    {
        Model::withoutEvents(function() {
            $this->nat = new Nat([
                'id' => 'nat-test'
            ]);
            $this->sync = new Task([
                'id' => 'sync-1',
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $this->sync->resource()->associate($this->nat);
        });

        Bus::fake();
        $job = new Update($this->sync);
        $job->handle();

        Bus::assertBatched(function (PendingBatch $batch) {
            return $batch->jobs->count() == 1 && count($batch->jobs->all()[0]) == 2;
        });
    }
}
