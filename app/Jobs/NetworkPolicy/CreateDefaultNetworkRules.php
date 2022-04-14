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
use UKFast\Admin\Monitoring\AdminClient;

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
    }

    public function handle()
    {
        foreach (config('defaults.network_policy.rules') as $rule) {
            if (!$this->model->networkRules()->where('name', $rule['name'])->where('type', $rule['type'])->exists()) {
                if ($this->model->network->isManaged() && $rule['name'] == 'Logic Monitor Collector') {
                    Log::info(get_class($this) . ': Skipping management resource');
                    continue;
                }
                $dhcpServerAddress = $this->model->network->getDhcpServerAddress()->toString();
                $networkRule = app()->make(NetworkRule::class);
                $networkRule->fill($rule);
                if ($rule['type'] == NetworkRule::TYPE_DHCP && $rule['direction'] == 'IN') {
                    $networkRule->source = $dhcpServerAddress;
                }

                if ($rule['type'] == NetworkRule::TYPE_CATCHALL && isset($this->data['catchall_rule_action'])) {
                    $networkRule->action = $this->data['catchall_rule_action'];
                }

                if ($rule['type'] == NetworkRule::TYPE_LOGICMONITOR) {
                    // identify LM collector for target AZ from monitoring API
                    $client = app()->make(AdminClient::class)
                        ->setResellerId($this->model->network->router->getResellerId());
                    $collectors = $client->collectors()->getAll([
                        'datacentre_id' => $this->model->network->router->availabilityZone->datacentre_site_id,
                        'is_shared' => true,
                    ]);

                    if (empty($collectors)) {
                        Log::info('No Collector found for datacentre', [
                            'availability_zone_id' => $this->model->network->router->availabilityZone->id,
                            'datacentre_site_id' => $this->model->network->router->availabilityZone->datacentre_site_id,
                        ]);
                        return;
                    }
                    $ipAddresses = [];
                    foreach ($collectors as $collector) {
                        $ipAddresses[] = $collector->ipAddress;
                    }
                    $networkRule->source = implode(',', $ipAddresses);
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
