<?php

namespace App\Listeners\V2\Vpc\Routers;

use App\Events\V2\Vpc\Deleted;
use App\Models\V2\Vpc;
use Illuminate\Support\Facades\Log;

class Delete
{
    public function handle(Deleted $event)
    {
        Log::info(get_class($this) . ' : Started', ['event' => $event]);
        $vpc = Vpc::withTrashed()->findOrFail($event->model->getKey());
        $vpc->routers()->each(function ($router) {
            $router->delete();
        });
        Log::info(get_class($this) . ' : Finished', ['event' => $event]);
    }
}
