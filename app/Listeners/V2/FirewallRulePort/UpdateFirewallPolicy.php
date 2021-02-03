<?php

namespace App\Listeners\V2\FirewallRulePort;

use App\Events\V2\FirewallRulePort\Saved;
use App\Jobs\Sync\FirewallPolicy\Save;
use Illuminate\Support\Facades\Log;

class UpdateFirewallPolicy
{
    public function handle(Saved $event)
    {
        Log::info(get_class($this) . ' : Started', ['event' => $event]);

        dispatch(new Save($event->model->firewallRule->firewallPolicy));

        Log::info(get_class($this) . ' : Finished', ['event' => $event]);
    }
}
