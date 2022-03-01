<?php

namespace App\Listeners\V2\Volume;

use App\Events\V2\Volume\Creating;
use App\Models\V2\Volume;
use Illuminate\Support\Facades\Log;

class DefaultIops
{
    public function handle(Creating $event)
    {
        /** @var Volume $model */
        $model = $event->model;

        Log::info('Setting default iops on volume');

        $model->iops = $model->iops ?? config('volume.iops.default');

        Log::info('Default iops on volume set to ' . $model->iops);
    }
}
