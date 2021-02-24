<?php

namespace App\Listeners\V2\FirewallRule;

use Illuminate\Support\Facades\Log;

class UpdateFirewallPolicy
{
    public function handle($event)
    {
        Log::info(get_class($this) . ' : Started', ['event' => $event]);

        if (!is_empty($event->model->firewallPolicy)) {
            $event->model->firewallPolicy->save();
        }

        Log::info(get_class($this) . ' : Finished', ['event' => $event]);
    }
}
