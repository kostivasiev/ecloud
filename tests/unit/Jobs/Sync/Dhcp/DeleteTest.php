<?php

namespace Tests\unit\Jobs\Sync\Dhcp;

use App\Jobs\Sync\Dhcp\Delete;
use App\Models\V2\Dhcp;
use App\Models\V2\Sync;
use Illuminate\Bus\PendingBatch;
use Illuminate\Support\Facades\Bus;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class DeleteTest extends TestCase
{
    use DatabaseMigrations;

    private $sync;
    private $dhcp;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testJobsBatched()
    {
        Sync::withoutEvents(function() {
            $dhcp = factory(Dhcp::class)->create([
                'id' => 'dhcp-test',
                'vpc_id' => 'vpc-test',
            ]);
            $this->sync = new Sync([
                'id' => 'sync-1',
            ]);
            $this->sync->resource()->associate($dhcp);
        });

        Bus::fake();
        $job = new Delete($this->sync);
        $job->handle();

        Bus::assertBatched(function (PendingBatch $batch) {
            return $batch->jobs->count() > 0;
        });
    }
}
