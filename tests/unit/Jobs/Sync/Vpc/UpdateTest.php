<?php

namespace Tests\unit\Jobs\Sync\Vpc;

use App\Jobs\Sync\Vpc\Update;
use App\Models\V2\Sync;
use Illuminate\Bus\PendingBatch;
use Illuminate\Support\Facades\Bus;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    private $sync;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testJobsBatched()
    {
        Sync::withoutEvents(function() {
            $this->sync = new Sync([
                'id' => 'sync-1',
            ]);
            $this->sync->resource()->associate($this->vpc());
        });

        Bus::fake();
        $job = new Update($this->sync);
        $job->handle();

        Bus::assertBatched(function (PendingBatch $batch) {
            return $batch->jobs->count() > 0;
        });
    }
}
