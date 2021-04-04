<?php

namespace Tests\unit\Jobs\Sync\Router;

use App\Jobs\Sync\Router\Delete;
use App\Models\V2\Sync;
use Illuminate\Bus\PendingBatch;
use Illuminate\Support\Facades\Bus;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class DeleteTest extends TestCase
{
    use DatabaseMigrations;

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
            $this->sync->resource()->associate($this->router());
        });

        Bus::fake();
        $job = new Delete($this->sync);
        $job->handle();

        Bus::assertBatched(function (PendingBatch $batch) {
            return $batch->jobs->count() == 1 && count($batch->jobs->all()[0]) == 5;
        });
    }
}
