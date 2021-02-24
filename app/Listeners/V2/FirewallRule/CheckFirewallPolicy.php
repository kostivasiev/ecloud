<?php

namespace App\Listeners\V2\FirewallRule;

use Illuminate\Support\Facades\Log;

class CheckFirewallPolicy
{
    public function handle($event)
    {
        Log::info(get_class($this) . ' : Started', ['event' => $event]);

        if ($event->model->firewallPolicy->getStatus() === 'in-progress') {
            return false;
        }

        Log::info(get_class($this) . ' : Finished', ['event' => $event]);
    }
}
