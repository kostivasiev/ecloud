<?php

namespace App\Jobs\VpnSession;

use App\Jobs\Job;
use App\Models\V2\FloatingIp;
use App\Models\V2\Nat;
use App\Models\V2\Nic;
use App\Models\V2\VpnSession;
use App\Models\V2\VpnSessionNetwork;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class CreateNats extends Job
{
    use Batchable, LoggableModelJob;

    private VpnSession $model;

    public function __construct(VpnSession $vpnSession)
    {
        $this->model = $vpnSession;
    }

    /**
     * Check if the floating IP was assigned to a NIC and create NATs
     */
    public function handle()
    {
        $vpnSession = $this->model;

        foreach ($vpnSession->getNetworksByType(VpnSessionNetwork::TYPE_LOCAL)->get() as $localNetwork) {
            foreach ($vpnSession->getNetworksByType(VpnSessionNetwork::TYPE_REMOTE)->get() as $remoteNetwork) {
                if ($localNetwork->localNoSNATs()->where("destination_id", "=", $remoteNetwork->id)->count() == 0) {
                    $nat = app()->make(Nat::class);
                    $nat->source()->associate($localNetwork);
                    $nat->destination()->associate($remoteNetwork);
                    $nat->action = Nat::ACTION_NOSNAT;
                    $nat->sequence = config('defaults.vpn_session.network.nosnat.sequence');
                    $task = $nat->syncSave();
                    Log::info(get_class($this) . ' : Creating No SNAT for local network ' . $localNetwork->id . ' to remote network ' . $remoteNetwork->id, ['task_id' => $task->id]);
                }
            }
        }
    }
}
