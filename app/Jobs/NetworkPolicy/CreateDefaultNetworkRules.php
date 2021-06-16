<?php

namespace App\Jobs\NetworkPolicy;

use App\Jobs\Job;
use App\Models\V2\NetworkPolicy;
use App\Models\V2\NetworkRule;
use App\Models\V2\NetworkRulePort;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;
use IPLib\Address\AddressInterface;
use IPLib\Range\Subnet;

class CreateDefaultNetworkRules extends Job
{
    use Batchable, LoggableModelJob;

    private NetworkPolicy $model;

    private ?AddressInterface $dhcpServerAddress;

    private $data;

    public function __construct(NetworkPolicy $networkPolicy, $data = null)
    {
        $this->model = $networkPolicy;
        $this->data = $data;

        $subnet = Subnet::fromString($this->model->network->subnet);
        $this->dhcpServerAddress = $subnet->getStartAddress()->getNextAddress()->getNextAddress();
    }

    public function handle()
    {
        foreach (config('defaults.network_policy.rules') as $rule) {
            if (!$this->model->networkRules()->where('name', $rule['name'])->where('type', $rule['type'])->exists()) {
                $networkRule = app()->make(NetworkRule::class);
                $networkRule->fill($rule);
                if ($rule['type'] == NetworkRule::TYPE_DHCP && $rule['direction'] == 'IN') {
                    $networkRule->source = $this->dhcpServerAddress;
                }

                if ($rule['type'] == NetworkRule::TYPE_CATCHALL && isset($this->data['catchall_rule_action'])) {
                    $networkRule->action = $this->data['catchall_rule_action'];
                }

                $this->model->networkRules()->save($networkRule);
                Log::info(get_class($this) . ': Created default network rule ' . $rule['name']);

                if (isset($rule['ports'])) {
                    foreach ($rule['ports'] as $port) {
                        $networkRulePort = app()->make(NetworkRulePort::class);
                        $networkRulePort->fill($port);
                        $networkRulePort->networkRule()->associate($networkRule);
                        $networkRulePort->save();
                    }
                }
            }
        }
    }
}
