<?php
namespace App\Listeners\V2\FirewallPolicy\FirewallRule\FirewallRulePort;

use App\Events\V2\FirewallRule\Deleted;
use App\Jobs\FirewallPolicy\DeleteFirewallRulePorts;

class Delete
{
    public function handle(Deleted $event)
    {
        dispatch(new DeleteFirewallRulePorts([
            'firewall_rule_id' => $event->model->getKey(),
        ]));
    }
}
