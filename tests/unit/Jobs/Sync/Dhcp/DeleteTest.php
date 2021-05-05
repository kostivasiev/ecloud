<?php

namespace Tests\unit\Jobs\Sync\Dhcp;

use App\Jobs\Sync\Dhcp\Delete;
use App\Models\V2\Dhcp;
use App\Models\V2\Task;
use App\Support\Sync;
use Illuminate\Bus\PendingBatch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Bus;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class DeleteTest extends TestCase
{
    private $sync;
    private $dhcp;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testJobsBatched()
    {
        Model::withoutEvents(function() {
            $dhcp = factory(Dhcp::class)->create([
                'id' => 'dhcp-test',
                'vpc_id' => 'vpc-test',
            ]);
            $this->sync = new Task([
                'id' => 'sync-1',
                'name' => Sync::TASK_NAME_DELETE,
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
