<?php

namespace App\Listeners\V2\FirewallRulePort;

use Illuminate\Support\Facades\Log;

class CheckFirewallPolicy
{
    public function handle($event)
    {
        Log::info(get_class($this) . ' : Started', ['event' => $event]);

        if (!empty($event->model->firewallRule) && !empty($event->model->firewallRule->firewallPolicy)) {
            if ($event->model->firewallRule->firewallPolicy->getStatus() === 'in-progress') {
                return false;
            }
        }

        Log::info(get_class($this) . ' : Finished', ['event' => $event]);
    }
}
