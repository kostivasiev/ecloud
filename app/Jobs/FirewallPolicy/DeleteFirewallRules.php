<?php

namespace App\Jobs\FirewallPolicy;

use App\Jobs\Job;
use App\Models\V2\FirewallPolicy;
use Illuminate\Support\Facades\Log;

class DeleteFirewallRules extends Job
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['data' => $this->data]);
        $firewallPolicy = FirewallPolicy::withTrashed()->findOrFail($this->data['firewall_policy_id']);
        $firewallPolicy->firewallRules()->each(function ($rule) {
            $rule->delete();
        });
        Log::info(get_class($this) . ' : Finished', ['data' => $this->data]);
    }
}