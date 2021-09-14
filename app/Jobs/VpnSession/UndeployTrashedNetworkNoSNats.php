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
            Log::warning("IN addNatForDeletion", ["natsToDelete"=>count($natsToDelete)]);
            foreach ($natsToDelete as $natToDelete) {
                Log::warning("CHECKING", ["nat->id" => $nat->id, "natToDelete->id" => $natToDelete->id]);
                if ($nat->id == $natToDelete->id) {
                    Log::warning("MATCHED EXISTING", ["nat->id" => $nat->id, "natToDelete->id" => $natToDelete->id]);
                    return;
                }
            }

            Log::warning("UNMATCHED, ADDING", ["nat->id" => $nat->id]);
            $natsToDelete[] = $nat;
        };

        foreach ($vpnSession->getNetworksByType(VpnSessionNetwork::TYPE_LOCAL)->withTrashed()->get() as $localNetwork) {
            if ($localNetwork->trashed()) {
                foreach ($localNetwork->localNoSNATs as $localNoSNAT) {
                    Log::warning("DEBUG !! ADDING NAT", ["localNoSNAT->id" => $localNoSNAT->id]);
                    $addNatForDeletion($localNoSNAT);
                }
            }
        }
        foreach ($vpnSession->getNetworksByType(VpnSessionNetwork::TYPE_REMOTE)->withTrashed()->get() as $remoteNetwork) {
            if ($remoteNetwork->trashed()) {
                foreach ($remoteNetwork->remoteNoSNATs as $remoteNoSNAT) {
                    Log::warning("DEBUG !! ADDING NAT", ["remoteNoSNAT->id" => $remoteNoSNAT->id]);
                    $addNatForDeletion($remoteNoSNAT);
                }
            }
        }

        if (count($natsToDelete) > 0) {
            Log::warning("WE HAVE NATS TO DELETE!", ["natsToDelete" => count($natsToDelete)]);
            $taskIDs = [];
            foreach ($natsToDelete as $natToDelete) {
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
