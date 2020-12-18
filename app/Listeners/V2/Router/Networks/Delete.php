<?php

namespace App\Listeners\V2\Router\Networks;

use App\Events\V2\Router\Deleted;
use App\Models\V2\Router;
use Illuminate\Support\Facades\Log;

class Delete
{
    public function handle(Deleted $event)
    {
        Log::info(get_class($this) . ' : Started', ['event' => $event]);
        $router = Router::withTrashed()->findOrFail($event->model->getKey());
        $router->networks()->each(function ($network) {
            $network->delete();
        });
        Log::info(get_class($this) . ' : Finished', ['event' => $event]);
    }
}
