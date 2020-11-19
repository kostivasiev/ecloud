<?php

namespace App\Listeners\V2\Vpc\Dhcp;

use App\Events\V2\Vpc\Deleted;
use App\Models\V2\Dhcp;

class Delete
{
    public function handle(Deleted $event)
    {
        Dhcp::findOrFail($event->dhcpId)->delete();
    }
}
