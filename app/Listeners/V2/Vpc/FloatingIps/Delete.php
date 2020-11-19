<?php

namespace App\Listeners\V2\Vpc\FloatingIps;

use App\Events\V2\Vpc\Deleted;
use App\Jobs\Vpc\Undeploy\DeleteFloatingIp;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class Delete
{
    use InteractsWithQueue;

    public function handle(Deleted $event)
    {
        $vpc = $event->model;
        $data = [
            'vpc_id' => $vpc->getKey(),
        ];
        dispatch(new DeleteFloatingIp($data));
    }
}
