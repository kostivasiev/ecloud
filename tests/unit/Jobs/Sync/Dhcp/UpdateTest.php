<?php

namespace Tests\unit\Jobs\Sync\Dhcp;

use App\Jobs\Sync\Dhcp\Update;
use App\Models\V2\Dhcp;
use App\Models\V2\Task;
use Illuminate\Bus\PendingBatch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Bus;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    private $task;

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
            $this->task = new Task([
                'id' => 'task-1',
            ]);
            $this->task->resource()->associate($dhcp);
        });

        Bus::fake();
        $job = new Update($this->task);
        $job->handle();

        Bus::assertBatched(function (PendingBatch $batch) {
            return $batch->jobs->count() > 0;
        });
    }
}
