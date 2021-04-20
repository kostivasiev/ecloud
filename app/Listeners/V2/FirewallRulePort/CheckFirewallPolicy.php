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

        $lock = Cache::lock($event->model->firewallRule->firewallPolicy->syncGetLockKey(), 60);
        try {
            Log::debug(get_class($this) . ' : Attempting to obtain lock for 60s', ['resource_id' => $event->model->firewallRule->firewallPolicy->id]);
            $lock->block(60);
            Log::debug(get_class($this) . ' : Lock obtained', ['resource_id' => $event->model->firewallRule->firewallPolicy->id]);

            if (!$event->model->firewallRule->firewallPolicy->canSync(Sync::TYPE_UPDATE)) {
                throw new SyncException("Cannot sync firewall policy");
            }
        } finally {
            $lock->release();
        }

        Log::info(get_class($this) . ' : Finished', ['event' => $event]);
    }
}
