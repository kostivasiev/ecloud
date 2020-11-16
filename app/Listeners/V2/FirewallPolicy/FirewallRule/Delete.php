<?php
namespace App\Listeners\V2\FirewallPolicy\FirewallRule;

use App\Events\V2\FirewallPolicy\Deleted;
use App\Jobs\FirewallPolicy\DeleteFirewallRules;

class Delete
{
    public function handle(Deleted $event)
    {
        dispatch(new DeleteFirewallRules([
            'firewall_policy_id' => $event->firewallPolicyId,
        ]));
    }
}
