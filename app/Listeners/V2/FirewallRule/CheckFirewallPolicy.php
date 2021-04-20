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

        if (!$event->model->firewallPolicy->canSync(Sync::TYPE_UPDATE)) {
            throw new SyncException();
        }

        Log::info(get_class($this) . ' : Finished', ['event' => $event]);
    }
}
