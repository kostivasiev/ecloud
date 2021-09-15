<?php

namespace App\Jobs\VpnSession;

use App\Jobs\Job;
use App\Models\V2\FloatingIp;
use App\Models\V2\Nat;
use App\Models\V2\Nic;
use App\Models\V2\Task;
use App\Models\V2\VpnSession;
use App\Models\V2\VpnSessionNetwork;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class SyncNetworkNoSNats extends Job
{
    use Batchable, LoggableModelJob;

    const TASK_WAIT_DATA_KEY = 'sync_network_no_snats_task_ids';

    private Task $task;
    private VpnSession $model;

    public function __construct(Task $task, VpnSession $vpnSession)
    {
        $this->task = $task;
        $this->model = $vpnSession;
    }

    public function handle()
    {
        $vpnSession = $this->model;

        $taskIDs = [];
        $deletedNatIDs = [];

        $shouldDeleteNat = function ($nat) use (&$deletedNatIDs) {
            foreach ($deletedNatIDs as $deletedNatID) {
                if ($nat->id == $deletedNatID) {
                    return false;
                }
            }

            return true;
        };

        foreach ($vpnSession->getNetworksByType(VpnSessionNetwork::TYPE_LOCAL)->withTrashed()->get() as $localNetwork) {
            if ($localNetwork->trashed()) {
                foreach ($localNetwork->localNoSNATs as $localNoSNAT) {
                    if ($shouldDeleteNat($localNoSNAT)) {
                        Log::warning("Removing No SNAT rule for deleted local network", ["vpn_session_network_id"=> $localNetwork->id, "nat_id" => $localNoSNAT->id]);
                        $task = $localNoSNAT->syncDelete();
                        $taskIDs[] = $task->id;
                        $deletedNatIDs[] = $localNoSNAT->id;
                    }
                }
            }
        }

        foreach ($vpnSession->getNetworksByType(VpnSessionNetwork::TYPE_REMOTE)->withTrashed()->get() as $remoteNetwork) {
            if ($remoteNetwork->trashed()) {
                foreach ($remoteNetwork->remoteNoSNATs as $remoteNoSNAT) {
                    if ($shouldDeleteNat($remoteNoSNAT)) {
                        Log::warning("Removing No SNAT rule for deleted remote network", ["vpn_session_network_id"=> $remoteNetwork->id, "nat_id" => $remoteNoSNAT->id]);
                        $task = $remoteNoSNAT->syncDelete();
                        $taskIDs[] = $task->id;
                        $deletedNatIDs[] = $remoteNoSNAT->id;
                    }
                }
            }
        }

        foreach ($vpnSession->getNetworksByType(VpnSessionNetwork::TYPE_LOCAL)->get() as $localNetwork) {
            foreach ($vpnSession->getNetworksByType(VpnSessionNetwork::TYPE_REMOTE)->get() as $remoteNetwork) {
                if ($localNetwork->localNoSNATs()->where("destination_id", "=", $remoteNetwork->id)->count() == 0) {
                    $nat = app()->make(Nat::class);
                    $nat->source()->associate($localNetwork);
                    $nat->destination()->associate($remoteNetwork);
                    $nat->action = Nat::ACTION_NOSNAT;
                    $nat->sequence = config('defaults.vpn_session.network.nosnat.sequence');
                    $task = $nat->syncSave();
                    $taskIDs[] = $task->id;
                    Log::info(get_class($this) . ' : Creating No SNAT for local network ' . $localNetwork->id . ' to remote network ' . $remoteNetwork->id, ['task_id' => $task->id]);
                }
            }
        }

        if (count($taskIDs) > 0) {
            $data = $this->task->data ?? [];
            $data[self::TASK_WAIT_DATA_KEY] = $taskIDs;

            $this->task->data = $data;
            $this->task->save();
        }
    }
}
