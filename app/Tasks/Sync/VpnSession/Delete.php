<?php

namespace App\Tasks\Sync\VpnSession;

use App\Jobs\Nsx\VpnSession\UndeployCheck;
use App\Jobs\VpnSession\DeletePreSharedKey;
use App\Jobs\VpnSession\Nsx\Undeploy;
use App\Jobs\VpnSession\RemoveNetworks;
use App\Jobs\VpnSession\SyncNetworkNoSNats;
use App\Tasks\Task;

class Delete extends Task
{
    public function jobs()
    {
        return [
            Undeploy::class,
            UndeployCheck::class,
            RemoveNetworks::class,
            SyncNetworkNoSNats::class,
            DeletePreSharedKey::class,
        ];
    }
}
