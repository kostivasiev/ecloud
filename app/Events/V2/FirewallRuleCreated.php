<?php

namespace App\Events\V2;

use App\Models\V2\FirewallRule;
use Illuminate\Queue\SerializesModels;

class FirewallRuleCreated
{
    use SerializesModels;

    /**
     * @var FirewallRule
     */
    public $firewallRule;

    /**
     * @param FirewallRule $firewallRule
     * @return void
     */
    public function __construct(FirewallRule $firewallRule)
    {
        $this->firewallRule = $firewallRule;
    }
}
