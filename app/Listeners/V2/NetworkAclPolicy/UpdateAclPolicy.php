<?php

namespace App\Listeners\V2\NetworkAclPolicy;

use App\Events\V2\NetworkAcl\Updated;
use Illuminate\Support\Facades\Log;

class UpdateAclPolicy
{
    public function handle(Updated $event)
    {
        Log::info(get_class($this) . ' : Started', ['event' => $event]);

        $model = $event->model;
        $model->setSyncCompleted();

        Log::info(get_class($this) . ' : Finished', ['event' => $event]);
    }
}
