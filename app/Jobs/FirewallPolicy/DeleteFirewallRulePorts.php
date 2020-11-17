<?php

namespace App\Jobs\FirewallPolicy;

use App\Jobs\Job;
use App\Models\V2\FirewallPolicy;
use App\Models\V2\FirewallRule;
use Illuminate\Support\Facades\Log;

class DeleteFirewallRulePorts extends Job
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['data' => $this->data]);
        $firewallRule = FirewallRule::withTrashed()->findOrFail($this->data['firewall_rule_id']);
        $firewallRule->firewallRulePorts()->each(function ($ports) {
            $ports->delete();
        });
        Log::info(get_class($this) . ' : Finished', ['data' => $this->data]);
    }
}
