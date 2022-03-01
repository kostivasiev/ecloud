<?php

namespace Tests\unit\Jobs\VpnSession;

use App\Events\V2\Task\Created;
use App\Events\V2\Task\Updated;
use App\Jobs\VpnSession\SyncNetworkNoSNats;
use App\Models\V2\Nat;
use App\Models\V2\Task;
use App\Models\V2\VpnSessionNetwork;
use App\Support\Sync;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Tests\Mocks\Resources\VpnSessionMock;
use Tests\TestCase;

class SyncNetworkNoSNatsTest extends TestCase
{
    use VpnSessionMock;

    protected Task $task;

    public function setUp(): void
    {
        parent::setUp();

        $this->vpnSession();
    }

    public function testSingleLocalAndRemoteNetworksOneNoSNATRuleCreated()
    {
        Event::fake([JobFailed::class, Created::class, Updated::class]);

        $this->vpnSession->vpnSessionNetworks()->create([
            'id' => 'vpnsn-testlocal1',
            'type' => VpnSessionNetwork::TYPE_LOCAL,
            'ip_address' => '1.1.1.1',
        ]);
        $this->vpnSession->vpnSessionNetworks()->create([
            'id' => 'vpnsn-testremote1',
            'type' => VpnSessionNetwork::TYPE_REMOTE,
            'ip_address' => '2.2.2.2',
        ]);

        $task = new Task([
            'id' => 'sync-test',
            'name' => Sync::TASK_NAME_UPDATE,
        ]);

        $task->resource()->associate($this->vpnSession);
        $task->save();

        dispatch(new SyncNetworkNoSNats($task, $this->vpnSession));

        $natCollection = Nat::where('source_id', '=', 'vpnsn-testlocal1')->get();
        $this->assertEquals(1, $natCollection->count());
        $nat = $natCollection->first();
        $this->assertEquals('vpnsn-testremote1', $nat->destination_id);
        $this->assertEquals(Nat::ACTION_NOSNAT, $nat->action);
    }

    public function testSingleLocalAndMultipleRemoteNetworksMultipleNoSNATRuleCreated()
    {
        Event::fake([JobFailed::class, Created::class, Updated::class]);

        $this->vpnSession->vpnSessionNetworks()->create([
            'id' => 'vpnsn-testlocal1',
            'type' => VpnSessionNetwork::TYPE_LOCAL,
            'ip_address' => '1.1.1.1',
        ]);
        $this->vpnSession->vpnSessionNetworks()->create([
            'id' => 'vpnsn-testremote1',
            'type' => VpnSessionNetwork::TYPE_REMOTE,
            'ip_address' => '2.2.2.2',
        ]);
        $this->vpnSession->vpnSessionNetworks()->create([
            'id' => 'vpnsn-testremote2',
            'type' => VpnSessionNetwork::TYPE_REMOTE,
            'ip_address' => '3.3.3.3',
        ]);

        $task = new Task([
            'id' => 'sync-test',
            'name' => Sync::TASK_NAME_UPDATE,
        ]);

        $task->resource()->associate($this->vpnSession);
        $task->save();

        dispatch(new SyncNetworkNoSNats($task, $this->vpnSession));

        $natCollection = Nat::where('source_id', '=', 'vpnsn-testlocal1')->get();
        $this->assertEquals(2, $natCollection->count());

        $this->assertTrue(!$natCollection->filter(function($nat) {
            return $nat->destination_id == 'vpnsn-testremote1';
        })->isEmpty());
        $this->assertTrue(!$natCollection->filter(function($nat) {
            return $nat->destination_id == 'vpnsn-testremote2';
        })->isEmpty());
    }

    public function testMultipleLocalAndMultipleRemoteNetworksMultipleNoSNATRuleCreated()
    {
        Event::fake([JobFailed::class, Created::class, Updated::class]);

        $this->vpnSession->vpnSessionNetworks()->create([
            'id' => 'vpnsn-testlocal1',
            'type' => VpnSessionNetwork::TYPE_LOCAL,
            'ip_address' => '1.1.1.1',
        ]);
        $this->vpnSession->vpnSessionNetworks()->create([
            'id' => 'vpnsn-testlocal2',
            'type' => VpnSessionNetwork::TYPE_LOCAL,
            'ip_address' => '8.8.8.8',
        ]);
        $this->vpnSession->vpnSessionNetworks()->create([
            'id' => 'vpnsn-testremote1',
            'type' => VpnSessionNetwork::TYPE_REMOTE,
            'ip_address' => '2.2.2.2',
        ]);
        $this->vpnSession->vpnSessionNetworks()->create([
            'id' => 'vpnsn-testremote2',
            'type' => VpnSessionNetwork::TYPE_REMOTE,
            'ip_address' => '3.3.3.3',
        ]);

        $task = new Task([
            'id' => 'sync-test',
            'name' => Sync::TASK_NAME_UPDATE,
        ]);

        $task->resource()->associate($this->vpnSession);
        $task->save();

        dispatch(new SyncNetworkNoSNats($task, $this->vpnSession));

        $natCollection = Nat::where('source_id', '=', 'vpnsn-testlocal1')
                            ->orWhere('source_id', '=', 'vpnsn-testlocal2')->get();

        $this->assertTrue(!$natCollection->filter(function($nat) {
            return $nat->source_id == 'vpnsn-testlocal1' && $nat->destination_id == 'vpnsn-testremote1';
        })->isEmpty());
        $this->assertTrue(!$natCollection->filter(function($nat) {
            return $nat->source_id == 'vpnsn-testlocal1' && $nat->destination_id == 'vpnsn-testremote2';
        })->isEmpty());
        $this->assertTrue(!$natCollection->filter(function($nat) {
            return $nat->source_id == 'vpnsn-testlocal2' && $nat->destination_id == 'vpnsn-testremote1';
        })->isEmpty());
        $this->assertTrue(!$natCollection->filter(function($nat) {
            return $nat->source_id == 'vpnsn-testlocal2' && $nat->destination_id == 'vpnsn-testremote2';
        })->isEmpty());

        $this->assertEquals(4, $natCollection->count());
    }

    public function testRemoteNetworkRemovedNoSNATRuleRemoved()
    {
        Event::fake([JobFailed::class, Created::class, Updated::class]);

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
        $remoteNetwork2 = $this->vpnSession->vpnSessionNetworks()->create([
            'id' => 'vpnsn-testremote2',
            'type' => VpnSessionNetwork::TYPE_REMOTE,
            'ip_address' => '3.3.3.3',
        ]);

        $nat1 = app()->make(Nat::class);
        $nat1->id = 'nat-test1';
        $nat1->source()->associate($localNetwork1);
        $nat1->destination()->associate($remoteNetwork1);
        $nat1->action = NAT::ACTION_NOSNAT;
        $nat1->save();

        $nat2 = app()->make(Nat::class);
        $nat2->id = 'nat-test2';
        $nat2->source()->associate($localNetwork1);
        $nat2->destination()->associate($remoteNetwork2);
        $nat2->action = NAT::ACTION_NOSNAT;
        $nat2->save();

        $remoteNetwork2->delete();

        $task = new Task([
            'id' => 'sync-test',
            'name' => Sync::TASK_NAME_UPDATE,
        ]);

        $task->resource()->associate($this->vpnSession);
        $task->save();

        dispatch(new SyncNetworkNoSNats($task, $this->vpnSession));

        $this->assertFalse($nat1->tasks()->where('name', '=', Sync::TASK_NAME_DELETE)->exists());
        $this->assertTrue($nat2->tasks()->where('name', '=', Sync::TASK_NAME_DELETE)->exists());
    }
}