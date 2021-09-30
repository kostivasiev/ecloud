<?php

namespace Tests\unit\Jobs\Network;

use App\Jobs\Network\AddManagementSegments;
use App\Listeners\V2\TaskCreated;
use App\Models\V2\Dhcp;
use App\Models\V2\Network;
use App\Models\V2\Router;
use App\Models\V2\Task;
use App\Support\Sync;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class AddManagementSegmentsTest extends TestCase
{
    private Task $task;
    protected Router $managementRouter;
    protected Network $managementNetwork;
    protected Dhcp $managementDhcp;

    public function setUp(): void
    {
        parent::setUp();

        Model::withoutEvents(function () {
            $this->managementRouter = factory(Router::class)->create([
                'id' => 'rtr-mgttest',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
                'router_throughput_id' => $this->routerThroughput()->id,
            ]);
            $this->managementNetwork = factory(Network::class)->create([
                'id' => 'net-mgttest',
                'name' => 'Management Network Test',
                'subnet' => '10.0.0.0/24',
                'router_id' => $this->managementRouter->id
            ]);
            $this->managementDhcp = factory(Dhcp::class)->create([
                'id' => 'dhcp-mgttest',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
            ]);
        });
    }

    public function testSkipIfNotManagementNetwork()
    {
        Event::fake(TaskCreated::class);
        Bus::fake();
        Model::withoutEvents(function () {
            $this->task = new Task([
                'id' => 'sync-1',
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $this->task->resource()->associate($this->router());
        });
        $job = new AddManagementSegments($this->task);
        $job->handle();
        $this->assertNull($this->task->data);
    }

    public function testDeployManagementSegments()
    {
        Model::withoutEvents(function () {
            $this->task = new Task([
                'id' => 'sync-1',
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $this->task->resource()->associate($this->router());
            $this->task->data = [
                'management_router_id' => $this->managementRouter->id,
                'management_network_id' => $this->managementNetwork->id,
            ];
        });
        $this->nsxServiceMock()
            ->allows('patch')
            ->withSomeOfArgs('policy/api/v1/infra/tier-1s/rtr-mgttest/segments/net-mgttest')
            ->andReturns(function () {
                return new Response(200);
            });

        $job = new AddManagementSegments($this->task);
        $this->assertNull($job->handle());
    }
}
