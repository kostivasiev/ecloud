<?php

namespace App\Listeners\V2\Router\Networks;

use App\Events\V2\Router\Deleted;
use App\Jobs\Router\Undeploy\DeleteNetworks;
use Illuminate\Support\Facades\Log;

class Delete
{
    public function handle(Deleted $event)
    {
        $router = $event->model;
        $data = [
            'router_id' => $router->getKey(),
        ];
        dispatch(new DeleteNetworks($data));
    }
}
