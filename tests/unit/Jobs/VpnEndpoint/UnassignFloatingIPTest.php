<?php

namespace Tests\Jobs\VpnEndpoint;

use App\Jobs\VpnEndpoint\UnassignFloatingIP;
use App\Listeners\V2\TaskCreated;
use App\Models\V2\FloatingIp;
use App\Models\V2\Task;
use App\Models\V2\VpnEndpoint;
use App\Models\V2\VpnService;
use App\Support\Sync;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class UnassignFloatingIPTest extends TestCase
{
    protected FloatingIp $floatingIp;
    protected VpnEndpoint $vpnEndpoint;
    protected VpnService $vpnService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->floatingIp = FloatingIp::withoutEvents(function () {
            return factory(FloatingIp::class)->create([
                'id' => 'fip-abc123xyz',
                'vpc_id' => $this->vpc()->id,
                'ip_address' => '203.0.113.1',
            ]);
        });
        $this->vpnService = factory(VpnService::class)->create([
            'router_id' => $this->router()->id,
        ]);
        $this->vpnEndpoint = factory(VpnEndpoint::class)->create(
            [
                'name' => 'Get Test',
                'vpn_service_id' => $this->vpnService->id,
            ]
        );
        $this->floatingIp->resource()->associate($this->vpnEndpoint);
        $this->floatingIp->save();
    }

    /** @test */
    public function unassignFloatingIpRegardlessOfState()
    {
        Task::withoutEvents(function () {
            $task = new Task([
                'id' => 'sync-1',
                'name' => Sync::TASK_NAME_UPDATE,
                'data' => [],
                'completed' => 0,
                'failure_reason' => 'A reason for the failure',
            ]);
            $task->resource()->associate($this->floatingIp);
            $task->save();
            $this->floatingIp->refresh();
        });

        $this->assertEquals(Sync::STATUS_FAILED, $this->floatingIp->sync->status);

        Event::fake([JobProcessed::class]);

        (new UnassignFloatingIP($this->vpnEndpoint))->handle();

        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }
}
