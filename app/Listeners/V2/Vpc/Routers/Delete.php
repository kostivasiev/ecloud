<?php

namespace App\Listeners\V2\Vpc\Routers;

use App\Events\V2\Vpc\Deleted;
use App\Jobs\Vpc\Undeploy\DeleteRouter;
use Illuminate\Support\Facades\Log;

class Delete
{
    public function handle(Deleted $event)
    {
        $vpc = $event->model;
        $data = [
            'vpc_id' => $vpc->getKey(),
        ];
        dispatch(new DeleteRouter($data));
    }
}
