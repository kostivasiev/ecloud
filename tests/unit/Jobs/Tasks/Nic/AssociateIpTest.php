<?php
namespace Tests\unit\Jobs\Tasks\Nic;

use App\Jobs\Tasks\Nic\AssociateIp;
use App\Models\V2\IpAddress;
use App\Models\V2\Task;
use Illuminate\Bus\PendingBatch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class AssociateIpTest extends TestCase
{
    private $task;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testJobsBatched()
    {
        $ipAddress = IpAddress::factory()->create([
            'network_id' => $this->network()->id,
            'type' => 'cluster'
        ]);

        Model::withoutEvents(function () use ($ipAddress) {
            $this->task = new Task([
                'id' => 'task-1',
                'name' => 'associate_id',
                'data' => [
                    'ip_address_id' => $ipAddress->id,
                ]
            ]);
            $this->task->resource()->associate($this->nic());
        });

        Bus::fake();
        $job = new AssociateIp($this->task);
        $job->handle();

        Bus::assertBatched(function (PendingBatch $batch) {
            return $batch->jobs->count() == 1 && count($batch->jobs->all()[0]) == 1;
        });
    }
}