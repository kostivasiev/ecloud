<?php

namespace App\Listeners\V2\FirewallRule;

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

        $lock = Cache::lock($event->model->firewallPolicy->syncGetLockKey(), 60);
        try {
            Log::debug(get_class($this) . ' : Attempting to obtain sync lock for 60s', ['resource_id' => $event->model->firewallPolicy->id]);
            $lock->block(60);
            Log::debug(get_class($this) . ' : Sync lock obtained', ['resource_id' => $event->model->firewallPolicy->id]);

            if (!$event->model->firewallPolicy->canSync(Sync::TYPE_UPDATE)) {
                throw new SyncException("Cannot sync firewall policy");
            }
        } finally {
            $lock->release();
        }

        Log::info(get_class($this) . ' : Finished', ['event' => $event]);
    }
}
