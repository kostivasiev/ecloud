<?php

namespace Tests\unit\Jobs\FloatingIp;

use App\Events\V2\Task\Created;
use App\Jobs\FloatingIp\CreateFloatingIp;
use App\Models\V2\Task;
use App\Models\V2\VpnEndpoint;
use App\Models\V2\VpnService;
use App\Support\Sync;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CreateFloatingIpTest extends TestCase
{
    protected CreateFloatingIp $job;
    protected VpnService $vpnService;
    protected VpnEndpoint $vpnEndpoint;

    public function setUp(): void
    {
        parent::setUp();
        $this->vpnService = VpnService::withoutEvents(function () {
            return factory(VpnService::class)->create([
                'id' => 'vpn-unittest',
                'router_id' => $this->router()->id,
            ]);
        });
        $this->vpnEndpoint = VpnEndpoint::withoutEvents(function () {
            return factory(VpnEndpoint::class)->create([
                'id' => 'vpne-unittest',
                'vpn_service_id' => $this->vpnService->id,
            ]);
        });
    }

    public function testSuccessful()
    {
        Event::fake([Created::class]);
        $task = Task::withoutEvents(function () {
            $task = new Task([
                'id' => 'task-1',
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $task->resource()->associate($this->vpnEndpoint);
            $task->save();
            return $task;
        });

        dispatch(new CreateFloatingIp($task));

        Event::assertNotDispatched(JobFailed::class);
    }
}