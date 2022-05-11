<?php

namespace Tests\Unit\Jobs\Nsx\Dhcp;

use App\Jobs\Nsx\Dhcp\Undeploy;
use App\Models\V2\Dhcp;
use App\Models\V2\Task;
use App\Support\Sync;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class UndeployTest extends TestCase
{
    protected $dhcp;
    protected Task $task;

    public function setUp(): void
    {
        parent::setUp();

        Model::withoutEvents(function () {
            $this->dhcp = Dhcp::factory()->create([
                'id' => 'dhcp-test',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
            ]);

            $this->task = new Task([
                'id' => 'sync-1',
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $this->task->resource()->associate($this->dhcp);
            $this->task->save();
        });
    }

    public function testSucceeds()
    {
        $this->nsxServiceMock()->expects('delete')
            ->withSomeOfArgs('/policy/api/v1/infra/dhcp-server-configs/dhcp-test')
            ->andReturnUsing(function () {
                return new Response(200);
            });

        Event::fake([JobFailed::class]);

        dispatch(new Undeploy($this->task));

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testFails()
    {
        $this->expectException(\Exception::class);

        $this->nsxServiceMock()->expects('delete')
            ->withSomeOfArgs('/policy/api/v1/infra/dhcp-server-configs/dhcp-test')
            ->andThrows(new \Exception());

        dispatch(new Undeploy($this->task));
    }
}
