<?php

namespace App\Jobs\NetworkPolicy;

use App\Jobs\Job;
use App\Models\V2\NetworkPolicy;
use App\Models\V2\NetworkRule;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;
use IPLib\Range\Subnet;

class CreateDefaultNetworkRules extends Job
{
    use Batchable, LoggableModelJob;

    private NetworkPolicy $model;

    private $data;

    public function __construct(NetworkPolicy $networkPolicy, $data = null)
    {
        $this->model = $networkPolicy;
        $this->data = $data;
    }

    public function handle()
    {
        if ($this->model->networkRules()->where('type', NetworkRule::TYPE_DHCP)->count() > 0) {
            Log::info('Default network rules already exists, nothing to do');
            return true;
        }

        $subnet = Subnet::fromString($this->model->network->subnet);
        $dhcpServerAddress = $subnet->getStartAddress()->getNextAddress()->getNextAddress();

        foreach (config('defaults.network_policy.rules') as $rule) {
            $networkRule = app()->make(NetworkRule::class);
            $networkRule->fill($rule);
            if ($rule['type'] == NetworkRule::TYPE_DHCP && $rule['direction'] == 'IN') {
                $networkRule->source = $dhcpServerAddress;
            }

            if ($rule['type'] == NetworkRule::TYPE_CATCHALL && isset($this->data['catchall_rule_action'])) {
                $networkRule->action = $this->data['catchall_rule_action'];
            }

            $this->model->networkRules()->save($networkRule);
        }
    }
}
