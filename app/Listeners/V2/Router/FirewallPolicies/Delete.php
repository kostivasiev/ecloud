<?php
namespace App\Listeners\V2\Router\FirewallPolicies;

use App\Events\V2\Router\Deleted;
use App\Jobs\Router\DeleteFirewallPolicies;

class Delete
{
    public function handle(Deleted $event)
    {
        dispatch(new DeleteFirewallPolicies([
            'router_id' => $event->routerId,
        ]));
    }
}