<?php

namespace Tests\unit\Jobs\Router\Defaults;

use App\Jobs\Router\Defaults\AwaitFirewallPolicySync;
use App\Models\V2\FloatingIp;
use App\Models\V2\Nat;
use App\Models\V2\Nic;
use App\Models\V2\FirewallPolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class AwaitFirewallPolicySyncTest extends TestCase
{
    use DatabaseMigrations;

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
            $sync = new Sync([
                'id' => 'sync-1',
                'completed' => true,
            ]);
            $sync->resource()->associate($this->firewallPolicy);
            $sync->save();
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
            $sync = new Sync([
                'id' => 'sync-1',
                'completed' => false,
                'failure_reason' => 'test',
            ]);
            $sync->resource()->associate($this->firewallPolicy);
            $sync->save();
        });

        Event::fake([JobFailed::class]);

        dispatch(new AwaitFirewallPolicySync($this->firewallPolicy));

        Event::assertDispatched(JobFailed::class);
    }

    public function testJobReleasedWhenSyncInProgress()
    {
        Model::withoutEvents(function() {
            $sync = new Sync([
                'id' => 'sync-1',
                'completed' => false,
            ]);
            $sync->resource()->associate($this->firewallPolicy);
            $sync->save();
        });

        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new AwaitFirewallPolicySync($this->firewallPolicy));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return $event->job->isReleased();
        });
    }
}
