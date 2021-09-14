<?php

namespace App\Jobs\VpnSession;

use App\Jobs\Job;
use App\Models\V2\FloatingIp;
use App\Models\V2\Nic;
use App\Models\V2\VpnSession;
use App\Models\V2\VpnSessionNetwork;
use App\Traits\V2\Jobs\AwaitResources;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class AwaitNatSync extends Job
{
    use Batchable, LoggableModelJob, AwaitResources;

    private $model;

    public function __construct(VpnSession $vpnSession)
    {
        $this->model = $vpnSession;
    }

    public function handle()
    {
        $vpnSession = $this->model;

        $resources = [];
        foreach ($vpnSession->getNetworksByType(VpnSessionNetwork::TYPE_LOCAL) as $localNetwork) {
            $resources[] = $localNetwork->localNoSNATs;
        }

        $this->awaitSyncableResources($resources);
    }
}
