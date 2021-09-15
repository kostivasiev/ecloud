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

class RemoveNetworks extends Job
{
    use Batchable, LoggableModelJob;

    private VpnSession $model;

    public function __construct(VpnSession $vpnSession)
    {
        $this->model = $vpnSession;
    }

    public function handle()
    {
        $vpnSession = $this->model;

        foreach ($vpnSession->vpnSessionNetworks as $vpnSessionNetwork) {
            $vpnSessionNetwork->delete();
        }
    }
}
