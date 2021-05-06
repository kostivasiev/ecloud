<?php

namespace Tests\unit\Jobs\Router\Defaults;

use App\Jobs\Router\Defaults\AwaitFirewallPolicySync;
use App\Models\V2\FloatingIp;
use App\Models\V2\Nat;
use App\Models\V2\Nic;
use App\Models\V2\FirewallPolicy;
use App\Models\V2\Task;
use App\Support\Sync;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class AwaitFirewallPolicySyncTest extends TestCase
{
    protected $firewallPolicy;

    public function setUp(): void
    {
        parent::setUp();


        $this->firewallPolicy = Model::withoutEvents(function () {
            return factory(FirewallPolicy::class)->create([
                'id' => 'fwp-test',
                'router_id' => $this->router()->id,
            ]);
        });
    }

    public function testJobSucceedsWhenSyncComplete()
    {
        Model::withoutEvents(function () {
            $task = new Task([
                'id' => 'task-1',
                'completed' => true,
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $task->resource()->associate($this->firewallPolicy);
            $task->save();
        });

        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new AwaitFirewallPolicySync($this->firewallPolicy));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }

    public function testJobFailedWhenSyncFailed()
    {
        Model::withoutEvents(function() {
            $task = new Task([
                'id' => 'task-1',
                'completed' => false,
                'failure_reason' => 'test',
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $task->resource()->associate($this->firewallPolicy);
            $task->save();
        });

        Event::fake([JobFailed::class]);

        dispatch(new AwaitFirewallPolicySync($this->firewallPolicy));

        Event::assertDispatched(JobFailed::class);
    }

    public function testJobReleasedWhenSyncInProgress()
    {
        Model::withoutEvents(function() {
            $task = new Task([
                'id' => 'task-1',
                'completed' => false,
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $task->resource()->associate($this->firewallPolicy);
            $task->save();
        });

        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new AwaitFirewallPolicySync($this->firewallPolicy));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return $event->job->isReleased();
        });
    }
}
