<?php

namespace Tests\Unit\Jobs\Router;

use App\Events\V2\Task\Created;
use App\Jobs\Router\CreateManagementFirewallPolicies;
use App\Models\V2\FirewallPolicy;
use App\Models\V2\Network;
use App\Models\V2\Router;
use App\Models\V2\Task;
use App\Support\Sync;
use App\Tasks\Vpc\CreateManagementInfrastructure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CreateManagementFirewallPoliciesTest extends TestCase
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

    public function testNoPoliciesAddedIfNoManagementNetwork()
    {
        Event::fake([JobFailed::class, JobProcessed::class, Created::class]);

        $task = new Task([
            'id' => 'sync-1',
            'name' => CreateManagementInfrastructure::TASK_NAME,
            'data' => [
                'management_router_id' => $this->managementRouter->id
            ]
        ]);
        $task->resource()->associate($this->vpc());
        $task->save();

        dispatch(new CreateManagementFirewallPolicies($task));

        $task->refresh();

        Event::assertNotDispatched(JobFailed::class);

        Event::assertNotDispatched(Created::class, function ($event) {
            return $event->model->resource instanceof FirewallPolicy
                && $event->model->name == Sync::TASK_NAME_UPDATE;
        });

        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }

    public function testFirewallPolicyExistsSkips()
    {
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

        $this->firewallPolicy();

        dispatch(new CreateManagementFirewallPolicies($this->task));

        $this->task->refresh();

        Event::assertNotDispatched(JobFailed::class);

        Event::assertNotDispatched(Created::class, function ($event) {
            return $event->model->resource instanceof FirewallPolicy
                && $event->model->name == Sync::TASK_NAME_UPDATE;
        });

        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }

    public function testAddFirewallPolicies()
    {
        Model::withoutEvents(function () {
            $this->task = new Task([
                'id' => 'sync-1',
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $this->router()->setAttribute('is_management', true)->saveQuietly();
            $this->task->resource()->associate($this->router());
            $this->task->updateData('management_router_id', $this->managementRouter->id);
            $this->task->updateData('management_network_id', $this->managementNetwork->id);
        });

        Bus::fake();

        $job = new CreateManagementFirewallPolicies($this->task);
        $job->handle();

        $firewallPolicy = FirewallPolicy::where('router_id', '=', $this->managementRouter->id)->first();
        $this->assertNotEmpty($firewallPolicy);
        $this->assertEquals(4222, $firewallPolicy->firewallRules->first()->firewallRulePorts->first()->destination);
    }
}
