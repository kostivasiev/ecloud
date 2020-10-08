<?php

namespace App\Listeners\V2\Network;

use App\Events\V2\Network\Creating;
use App\Models\V2\Instance;
use Illuminate\Support\Facades\Log;

class DefaultSubnet
{
    public function handle(Creating $event)
    {
        /** @var Instance $model */
        $model = $event->model;

        Log::info('Setting default subnet on network ' . $model->id);

        $model->subnet = $model->subnet ?? config('defaults.network.subnets.range');

        Log::info('Default subnet on network ' . $model->id . ' set to ' . $model->subnet);
    }
}
