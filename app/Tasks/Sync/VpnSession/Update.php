<?php

namespace App\Tasks\Sync\VpnSession;

use App\Jobs\Nsx\VpnSession\CreateVpnSession;
use App\Jobs\VpnSession\CreatePreSharedKey;
use App\Jobs\VpnSession\SyncNetworkNoSNats;
use App\Tasks\Task;

class Update extends Task
{
    public function jobs()
    {
        return [
            CreatePreSharedKey::class,
            CreateVpnSession::class,
            SyncNetworkNoSNats::class,
        ];
    }
}
