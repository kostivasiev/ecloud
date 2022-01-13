<?php

namespace App\Jobs\VpnSession;

use App\Jobs\TaskJob;
use App\Models\V2\Nat;
use App\Models\V2\Task;
use App\Models\V2\VpnSessionNetwork;
use App\Traits\V2\TaskJobs\AwaitTask;

class SyncNetworkNoSNats extends TaskJob
{
    use AwaitTask;

    const TASK_WAIT_DATA_KEY = 'sync_network_no_snats_task_ids';

    public function handle()
    {
        $vpnSession = $this->task->resource;

        $tasks = null;

        if (!isset($this->task->data[self::TASK_WAIT_DATA_KEY])) {
            $tasks = [];
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
                            $this->warning("Removing No SNAT rule for deleted local network", ["vpn_session_network_id" => $localNetwork->id, "nat_id" => $localNoSNAT->id]);
                            $tasks[] = $localNoSNAT->syncDelete();
                            $deletedNatIDs[] = $localNoSNAT->id;
                        }
                    }
                }
            }

            foreach ($vpnSession->getNetworksByType(VpnSessionNetwork::TYPE_REMOTE)->withTrashed()->get() as $remoteNetwork) {
                if ($remoteNetwork->trashed()) {
                    foreach ($remoteNetwork->remoteNoSNATs as $remoteNoSNAT) {
                        if ($shouldDeleteNat($remoteNoSNAT)) {
                            $this->warning("Removing No SNAT rule for deleted remote network", ["vpn_session_network_id" => $remoteNetwork->id, "nat_id" => $remoteNoSNAT->id]);
                            $tasks[] = $remoteNoSNAT->syncDelete();
                            $deletedNatIDs[] = $remoteNoSNAT->id;
                        }
                    }
                }
            }

            foreach ($vpnSession->getNetworksByType(VpnSessionNetwork::TYPE_LOCAL)->get() as $localNetwork) {
                foreach ($vpnSession->getNetworksByType(VpnSessionNetwork::TYPE_REMOTE)->get() as $remoteNetwork) {
                    if ($localNetwork->localNoSNATs()->where("destination_id", "=", $remoteNetwork->id)->count() == 0) {
                        $this->info('Creating No SNAT for local network ' . $localNetwork->id . ' to remote network ' . $remoteNetwork->id);
                        $nat = app()->make(Nat::class);
                        $nat->source()->associate($localNetwork);
                        $nat->destination()->associate($remoteNetwork);
                        $nat->action = Nat::ACTION_NOSNAT;
                        $nat->sequence = config('defaults.vpn_session.network.nosnat.sequence');
                        $tasks[] = $nat->syncSave();
                    }
                }
            }

            if (count($tasks) > 0) {
                $data = $this->task->data ?? [];
                $data[self::TASK_WAIT_DATA_KEY] = collect($tasks)->pluck('id');

                $this->task->data = $data;
                $this->task->save();
            }
        } else {
            if (is_array($this->task->data[self::TASK_WAIT_DATA_KEY])) {
                $tasks = [];
                foreach ($this->task->data[self::TASK_WAIT_DATA_KEY] as $taskId) {
                    $tasks[] = Task::findOrFail($taskId);
                }
            }
        }

        if (!empty($tasks)) {
            $this->awaitTaskWithRelease(...$tasks);
        }
    }
}
