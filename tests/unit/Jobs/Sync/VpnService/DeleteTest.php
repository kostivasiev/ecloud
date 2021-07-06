<?php
namespace Tests\unit\Jobs\Sync\VpnService;

use App\Jobs\Sync\VpnService\Delete;
use App\Models\V2\Task;
use App\Models\V2\VpnService;
use App\Support\Sync;
use Illuminate\Bus\PendingBatch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class DeleteTest extends TestCase
{
    private $task;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testJobsBatched()
    {
        Model::withoutEvents(function () {
            $vpnService = factory(VpnService::class)->create([
                'id' => 'vpn-aaaaaaaa',
                'router_id' => $this->router()->id,
            ]);
            $this->task = new Task([
                'id' => 'task-1',
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $this->task->resource()->associate($vpnService);
        });

        Bus::fake();
        $job = new Delete($this->task);
        $job->handle();

        Bus::assertBatched(function (PendingBatch $batch) {
            return $batch->jobs->count() > 0;
        });
    }
}