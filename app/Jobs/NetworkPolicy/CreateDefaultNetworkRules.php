<?php

namespace App\Jobs\NetworkPolicy;

use App\Jobs\Job;
use App\Models\V2\NetworkPolicy;
use App\Models\V2\NetworkRule;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;
use IPLib\Range\Subnet;

class CreateDefaultNetworkRules extends Job
{
    use Batchable;

    private NetworkPolicy $networkPolicy;

    public function __construct(NetworkPolicy $networkPolicy)
    {
        $this->networkPolicy = $networkPolicy;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->networkPolicy->id]);

        if($this->networkPolicy->networkRules()->where('type', 'DHCP_Ingress')->orWhere('type', 'DHCP_Egress')->count() > 0) {
            Log::info('Default DHCP rules already exists, nothing to do');
            return true;
        }

        $this->networkPolicy->withTaskLock(function () {
            $subnet = Subnet::fromString($this->networkPolicy->network->subnet);
            $dhcpServerAddress = $subnet->getStartAddress()->getNextAddress()->getNextAddress();

            foreach (config('defaults.network_policy.rules') as $rule) {
                $networkRule = app()->make(NetworkRule::class);
                $networkRule->fill($rule);
                if ($rule['type'] == 'DHCP_Ingress') {
                    $networkRule->source = $dhcpServerAddress;
                }

                $this->networkPolicy->networkRules()->save($networkRule);
            }
        });

        Log::info(get_class($this) . ' : Finished', ['id' => $this->networkPolicy->id]);
    }
}
