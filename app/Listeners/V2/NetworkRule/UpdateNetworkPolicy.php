<?php

namespace App\Listeners\V2\NetworkRule;

use Illuminate\Support\Facades\Log;

class UpdateNetworkPolicy
{
    public function handle($event)
    {
        Log::info(get_class($this) . ' : Started', ['event' => $event]);

        $event->model->networkPolicy->save();

        Log::info(get_class($this) . ' : Finished', ['event' => $event]);
    }
}
