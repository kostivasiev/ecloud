<?php

namespace App\Listeners\V2\FirewallRulePort;

use Illuminate\Support\Facades\Log;

class UpdateFirewallPolicy
{
    public function handle($event)
    {
        Log::info(get_class($this) . ' : Started', ['event' => $event]);

        $event->model->firewallRule->firewallPolicy->save();
        $event->model->firewallPolicy->syncUnlock();

        Log::info(get_class($this) . ' : Finished', ['event' => $event]);
    }
}
