<?php

namespace Tests\unit\Jobs\Network;

use App\Jobs\Network\CreateManagementNetwork;
use App\Listeners\V2\TaskCreated;
use App\Models\V2\Network;
use App\Models\V2\Router;
use App\Models\V2\Task;
use App\Support\Sync;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use IPLib\Factory;
use IPLib\Range\Subnet;
use Tests\TestCase;

class CreateManagementNetworkTest extends TestCase
{
    protected Task $task;
    protected Router $managementRouter;
    public bool $first = true;

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
        });
    }

    public function testJobIsSkippedIfNoManagementRouter()
    {
        Model::withoutEvents(function () {
            $this->task = new Task([
                'id' => 'sync-1',
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $this->task->resource()->associate($this->router());
        });
        Event::fake(TaskCreated::class);
        Bus::fake();
        $job = new CreateManagementNetwork($this->task);
        $job->handle();
        $this->assertNull($this->task->data);
    }

    public function testManagementNetworkIsCreated()
    {
        Model::withoutEvents(function () {
            $this->task = new Task([
                'id' => 'sync-1',
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $this->router()->setAttribute('is_hidden', true)->saveQuietly();
            $this->task->resource()->associate($this->router());
            $this->task->data = [
                'management_router_id' => $this->managementRouter->id,
            ];
        });
        Event::fake(TaskCreated::class);
        Bus::fake();
        $job = new CreateManagementNetwork($this->task);
        $job->handle();

        $managementNetwork = Network::find($this->task->data['management_network_id']);
        $this->assertNotNull($managementNetwork);
        $this->assertEquals(config('network.subnet.standard'), $managementNetwork->subnet);
        $this->assertEquals($this->managementRouter->id, $managementNetwork->router_id);
    }

    public function testManagementNetworkOnAdvancedRange()
    {
        $this->vpc()->setAttribute('advanced_networking', true)->saveQuietly();
        Model::withoutEvents(function () {
            $this->task = new Task([
                'id' => 'sync-1',
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $this->router()->setAttribute('is_hidden', true)->saveQuietly();
            $this->task->resource()->associate($this->router());
            $this->task->data = [
                'management_router_id' => $this->managementRouter->id,
            ];
        });
        Event::fake(TaskCreated::class);
        Bus::fake();
        $job = new CreateManagementNetwork($this->task);
        $job->handle();

        $managementNetwork = Network::find($this->task->data['management_network_id']);
        $this->assertNotNull($managementNetwork);
        $this->assertEquals(config('network.subnet.advanced'), $managementNetwork->subnet);
        $this->assertEquals($this->managementRouter->id, $managementNetwork->router_id);
    }

    public function testSubnetAvailability()
    {
        $job = \Mockery::mock(CreateManagementNetwork::class)->makePartial();
        $subnet = $job->getNextAvailableSubnet('192.168.0.0/17', $this->availabilityZone()->id);
        $this->assertEquals('192.168.4.0/28', $subnet);

        // If 192.168.4.0/28 is in use, then next address should be used 192.168.4.16/28
        $this->network()->setAttribute('subnet', '192.168.4.0/28')->saveQuietly();
        $subnet = $job->getNextAvailableSubnet('192.168.0.0/17', $this->availabilityZone()->id);
        $this->assertEquals('192.168.4.16/28', $subnet);
    }
}
