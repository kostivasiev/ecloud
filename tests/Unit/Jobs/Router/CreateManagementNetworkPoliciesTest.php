<?php

namespace Tests\Unit\Jobs\Router;

use App\Events\V2\Task\Created;
use App\Jobs\Router\CreateManagementNetworkPolicies;
use App\Models\V2\Network;
use App\Models\V2\NetworkPolicy;
use App\Models\V2\Router;
use App\Models\V2\Task;
use App\Support\Sync;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CreateManagementNetworkPoliciesTest extends TestCase
{
    protected Task $task;
    protected Router $managementRouter;
    protected Network $managementNetwork;

    protected function setUp(): void
    {
        parent::setUp();
        $this->managementRouter = Router::factory()->create([
            'id' => 'rtr-mgmt',
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone()->id,
            'router_throughput_id' => $this->routerThroughput()->id,
        ]);
        $this->managementNetwork = Network::factory()->create([
            'id' => 'net-mgmt',
            'name' => 'Manchester Network',
            'subnet' => '10.0.0.0/24',
            'router_id' => $this->router()->id
        ]);
    }

    public function testNoChangeIfAdvancedNetworkingNotEnabled()
    {
        $this->vpc()->setAttribute('advanced_networking', false)->saveQuietly();
        Model::withoutEvents(function () {
            $this->task = new Task([
                'id' => 'sync-1',
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $this->router()->setAttribute('is_management', true)->saveQuietly();
            $this->task->resource()->associate($this->vpc());
        });

        Bus::fake();
        $job = new CreateManagementNetworkPolicies($this->task);
        $job->handle();

        $this->assertEquals(0, NetworkPolicy::where('network_id', '=', $this->managementNetwork->id)->count());
    }

    public function testAddNetworkPolicies()
    {
        $this->vpc()->setAttribute('advanced_networking', true)->saveQuietly();
        Model::withoutEvents(function () {
            $this->task = new Task([
                'id' => 'sync-1',
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $this->router()->setAttribute('is_management', true)->saveQuietly();
            $this->task->resource()->associate($this->vpc());
            $this->task->updateData('management_router_id', $this->managementRouter->id);
            $this->task->updateData('management_network_id', $this->managementNetwork->id);
        });

        Bus::fake();

        $job = new CreateManagementNetworkPolicies($this->task);
        $job->handle();

        $networkPolicy = NetworkPolicy::where('network_id', '=', $this->managementNetwork->id)->first();
        $this->assertNotEmpty($networkPolicy);
        $this->assertEquals(4222, $networkPolicy->networkRules->first()->networkRulePorts->first()->destination);
    }

    public function testNetworkPolicyExistsSkips()
    {
        $this->vpc()->setAttribute('advanced_networking', true)->saveQuietly();

        Event::fake([JobFailed::class, JobProcessed::class, Created::class]);

        Model::withoutEvents(function () {
            $this->task = new Task([
                'id' => 'sync-1',
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $this->router()->setAttribute('is_management', true)->saveQuietly();
            $this->task->resource()->associate($this->router());
            $this->task->updateData('management_router_id', $this->router()->id);
            $this->task->updateData('management_network_id', $this->network()->id);
        });

        $this->networkPolicy();

        dispatch(new CreateManagementNetworkPolicies($this->task));

        $this->task->refresh();

        Event::assertNotDispatched(JobFailed::class);

        Event::assertNotDispatched(Created::class, function ($event) {
            return $event->model->resource instanceof NetworkPolicy
                && $event->model->name == Sync::TASK_NAME_UPDATE;
        });

        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }
}
