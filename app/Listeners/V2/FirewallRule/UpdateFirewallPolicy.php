<?php

namespace App\Listeners\V2\FirewallRule;

use App\Events\V2\FirewallRule\Saved;
use App\Jobs\Sync\FirewallPolicy\Save;
use Illuminate\Support\Facades\Log;

class UpdateFirewallPolicy
{
    public function handle(Saved $event)
    {
        Log::info(get_class($this) . ' : Started', ['event' => $event]);

        dispatch(new Save($event->model->firewallPolicy));

        Log::info(get_class($this) . ' : Finished', ['event' => $event]);
    }
}
