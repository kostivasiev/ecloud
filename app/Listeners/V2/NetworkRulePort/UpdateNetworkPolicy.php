<?php

namespace App\Listeners\V2\NetworkRulePort;

use Illuminate\Support\Facades\Log;

class UpdateNetworkPolicy
{
    public function handle($event)
    {
        Log::info(get_class($this) . ' : Started', ['event' => $event]);

        $event->model->networkRule->networkPolicy->save();

        Log::info(get_class($this) . ' : Finished', ['event' => $event]);
    }
}
