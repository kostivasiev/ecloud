<?php

namespace Tests\unit\Jobs\Tasks;

use App\Events\V2\Task\Created;
use App\Jobs\Tasks\AwaitTasks;
use App\Jobs\VpnSession\SyncNetworkNoSNats;
use App\Models\V2\Nat;
use App\Models\V2\Task;
use App\Models\V2\VpnSessionNetwork;
use App\Support\Sync;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Tests\Mocks\Resources\VpnSessionMock;
use Tests\TestCase;

class AwaitTasksTest extends TestCase
{
    use VpnSessionMock;

    public function setUp(): void
    {
        parent::setUp();

        $this->vpnSession();
    }

    public function testJobFailedWhenTaskFailed()
    {
        Event::fake([JobFailed::class, Created::class]);

        $localNetwork1 = $this->vpnSession->vpnSessionNetworks()->create([
            'id' => 'vpnsn-testlocal1',
            'type' => VpnSessionNetwork::TYPE_LOCAL,
            'ip_address' => '1.1.1.1',
        ]);
        $remoteNetwork1 = $this->vpnSession->vpnSessionNetworks()->create([
            'id' => 'vpnsn-testremote1',
            'type' => VpnSessionNetwork::TYPE_REMOTE,
            'ip_address' => '2.2.2.2',
        ]);

        $nat1 = app()->make(Nat::class);
        $nat1->id = 'nat-test1';
        $nat1->source()->associate($localNetwork1);
        $nat1->destination()->associate($remoteNetwork1);
        $nat1->action = NAT::ACTION_NOSNAT;
        $nat1->save();

        $natDeleteTask = new Task([
            'id' => 'sync-natdelete',
            'name' => Sync::TASK_NAME_DELETE,
            'failure_reason' => 'test'
        ]);

        $natDeleteTask->resource()->associate($nat1);
        $natDeleteTask->save();

        $task = new Task([
            'id' => 'sync-test',
            'name' => Sync::TASK_NAME_UPDATE,
            'data' => [SyncNetworkNoSNats::TASK_WAIT_DATA_KEY => ['sync-natdelete']]
        ]);

        $task->resource()->associate($this->vpnSession);
        $task->save();

        Event::fake([JobFailed::class]);

        dispatch(new AwaitTasks($task, SyncNetworkNoSNats::TASK_WAIT_DATA_KEY));

        Event::assertDispatched(JobFailed::class);
    }

    public function testJobSucceedsWhenTaskCompleted()
    {
        Event::fake([JobFailed::class, Created::class]);

        $localNetwork1 = $this->vpnSession->vpnSessionNetworks()->create([
            'id' => 'vpnsn-testlocal1',
            'type' => VpnSessionNetwork::TYPE_LOCAL,
            'ip_address' => '1.1.1.1',
        ]);
        $remoteNetwork1 = $this->vpnSession->vpnSessionNetworks()->create([
            'id' => 'vpnsn-testremote1',
            'type' => VpnSessionNetwork::TYPE_REMOTE,
            'ip_address' => '2.2.2.2',
        ]);

        $nat1 = app()->make(Nat::class);
        $nat1->id = 'nat-test1';
        $nat1->source()->associate($localNetwork1);
        $nat1->destination()->associate($remoteNetwork1);
        $nat1->action = NAT::ACTION_NOSNAT;
        $nat1->save();

        $natDeleteTask = new Task([
            'id' => 'sync-natdelete',
            'name' => Sync::TASK_NAME_DELETE,
            'completed' => true
        ]);

        $natDeleteTask->resource()->associate($nat1);
        $natDeleteTask->save();

        $task = new Task([
            'id' => 'sync-test',
            'name' => Sync::TASK_NAME_UPDATE,
            'data' => [SyncNetworkNoSNats::TASK_WAIT_DATA_KEY => ['sync-natdelete']]
        ]);

        $task->resource()->associate($this->vpnSession);
        $task->save();

        Event::fake([JobFailed::class]);

        dispatch(new AwaitTasks($task, SyncNetworkNoSNats::TASK_WAIT_DATA_KEY));

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testJobReleasedWhenTaskInProgress()
    {
        Event::fake([JobFailed::class, JobProcessed::class, Created::class]);

        $localNetwork1 = $this->vpnSession->vpnSessionNetworks()->create([
            'id' => 'vpnsn-testlocal1',
            'type' => VpnSessionNetwork::TYPE_LOCAL,
            'ip_address' => '1.1.1.1',
        ]);
        $remoteNetwork1 = $this->vpnSession->vpnSessionNetworks()->create([
            'id' => 'vpnsn-testremote1',
            'type' => VpnSessionNetwork::TYPE_REMOTE,
            'ip_address' => '2.2.2.2',
        ]);

        $nat1 = app()->make(Nat::class);
        $nat1->id = 'nat-test1';
        $nat1->source()->associate($localNetwork1);
        $nat1->destination()->associate($remoteNetwork1);
        $nat1->action = NAT::ACTION_NOSNAT;
        $nat1->save();

        $natDeleteTask = new Task([
            'id' => 'sync-natdelete',
            'name' => Sync::TASK_NAME_DELETE,
        ]);

        $natDeleteTask->resource()->associate($nat1);
        $natDeleteTask->save();

        $task = new Task([
            'id' => 'sync-test',
            'name' => Sync::TASK_NAME_UPDATE,
            'data' => [SyncNetworkNoSNats::TASK_WAIT_DATA_KEY => ['sync-natdelete']]
        ]);

        $task->resource()->associate($this->vpnSession);
        $task->save();

        dispatch(new AwaitTasks($task, SyncNetworkNoSNats::TASK_WAIT_DATA_KEY));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return $event->job->isReleased();
        });
    }
}
