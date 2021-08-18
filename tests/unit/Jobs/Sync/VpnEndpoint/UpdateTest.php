<?php
namespace Tests\unit\Jobs\Sync\VpnEndpoint;

use App\Jobs\Sync\VpnEndpoint\Update;
use App\Models\V2\Task;
use App\Models\V2\VpnEndpoint;
use App\Models\V2\VpnService;
use App\Support\Sync;
use Illuminate\Bus\PendingBatch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Bus;
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
        Model::withoutEvents(function () {
            $vpnService = factory(VpnService::class)->create([
                'id' => 'vpn-aaaaaaaa',
                'router_id' => $this->router()->id,
            ]);
            $vpnEndpoint = factory(VpnEndpoint::class)->create([
                'id' => 'vpne-aaaaaaaa',
                'name' => 'PHP Unit VPN Endpoint',
                'vpn_service_id' => $vpnService->id,
            ]);
            $this->task = new Task([
                'id' => 'task-1',
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $this->task->resource()->associate($vpnEndpoint);
        });

        Bus::fake();
        $job = new Update($this->task);
        $job->handle();

        Bus::assertBatched(function (PendingBatch $batch) {
            return $batch->jobs->count() > 0;
        });
    }
}
