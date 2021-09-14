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

class UndeployTrashedNetworkNoSNats extends Job
{
    use Batchable, LoggableModelJob;

    const TASK_WAIT_DATA_KEY = 'undeploy_trashed_network_no_snats_task_ids';

    private Task $task;
    private VpnSession $model;

    public function __construct(Task $task, VpnSession $vpnSession)
    {
        $this->task = $task;
        $this->model = $vpnSession;
    }

    /**
     * Check if the floating IP was assigned to a NIC and create NATs
     */
    public function handle()
    {
        $vpnSession = $this->model;

        $natsToDelete = [];

        $addNatForDeletion = function($nat) use (&$natsToDelete) {
            foreach ($natsToDelete as $natToDelete) {
                if ($nat->id == $natToDelete->id) {
                    return;
                }
            }

            $natsToDelete[] = $nat;
        };

        foreach ($vpnSession->getNetworksByType(VpnSessionNetwork::TYPE_LOCAL)->withTrashed()->get() as $localNetwork) {
            if ($localNetwork->trashed()) {
                foreach ($localNetwork->localNoSNATs as $localNoSNAT) {
                    Log::warning("Adding local No SNAT rule for deletiong", ["nat_id" => $localNoSNAT->id]);
                    $addNatForDeletion($localNoSNAT);
                }
            }
        }
        foreach ($vpnSession->getNetworksByType(VpnSessionNetwork::TYPE_REMOTE)->withTrashed()->get() as $remoteNetwork) {
            if ($remoteNetwork->trashed()) {
                foreach ($remoteNetwork->remoteNoSNATs as $remoteNoSNAT) {
                    Log::warning("Adding remote No SNAT rule for deletiong", ["nat_id" => $remoteNoSNAT->id]);
                    $addNatForDeletion($remoteNoSNAT);
                }
            }
        }

        if (count($natsToDelete) > 0) {
            $taskIDs = [];
            foreach ($natsToDelete as $natToDelete) {
                Log::warning("Removing No SNAT rule", ["nat_id" => $natToDelete->id]);
                $task = $natToDelete->syncDelete();
                $taskIDs[] = $task->id;
            }

            $data = $this->task->data ?? [];
            $data[self::TASK_WAIT_DATA_KEY] = $taskIDs;

            $this->task->data = $data;
            $this->task->save();
        }
    }
}
