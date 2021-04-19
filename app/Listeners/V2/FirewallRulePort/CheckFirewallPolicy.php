<?php

namespace App\Listeners\V2\FirewallRulePort;

use App\Exceptions\SyncException;
use App\Models\V2\Sync;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CheckFirewallPolicy
{
    public function handle($event)
    {
        Log::info(get_class($this) . ' : Started', ['event' => $event]);

        $event->model->firewallRule->firewallPolicy->syncLock();

        try {
            if (!$event->model->firewallRule->firewallPolicy->canSync(Sync::TYPE_UPDATE)) {
                throw new SyncException("Cannot sync firewall policy");
            }
        }finally {
            $event->model->firewallRule->firewallPolicy->syncUnlock();
        }

        Log::info(get_class($this) . ' : Finished', ['event' => $event]);
    }
}
