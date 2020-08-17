<?php

namespace App\Events\V2;

use Illuminate\Queue\SerializesModels;
use App\Models\V2\FirewallRule;

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
