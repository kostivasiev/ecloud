<?php

namespace App\Listeners\V2\Nsx\Dhcp;

use App\Events\V2\Dhcp\Deleted;

class Delete
{
    public function handle(Deleted $event)
    {
        dispatch(new \App\Jobs\Nsx\Dhcp\Delete([
            'id' => $event->id,
        ]));
    }
}
