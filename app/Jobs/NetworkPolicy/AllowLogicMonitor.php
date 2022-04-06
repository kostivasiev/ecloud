<?php

namespace App\Jobs\NetworkPolicy;

use App\Jobs\Job;
use App\Models\V2\NetworkPolicy;
use App\Models\V2\NetworkRule;
use App\Models\V2\NetworkRulePort;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;
use UKFast\Admin\Monitoring\AdminClient;

class AllowLogicMonitor extends Job
{
    use Batchable, LoggableModelJob;

    private NetworkPolicy $model;

    public function __construct(NetworkPolicy $networkPolicy)
    {
        $this->model = $networkPolicy;
    }

    public function handle()
    {
        $network = $this->model->network;
        $router = $network->router;

        if (!$router->vpc->advanced_networking) {
            Log::info('Advanced Networking not enabled for Vpc, skipping.', [
                'vpc_id' => $router->vpc->id,
                'router_id' => $router->id,
                'network_policy_id' => $this->model->id,
            ]);
            return;
        }

        // Get collector information
        $client = app()->make(AdminClient::class)
            ->setResellerId($router->getResellerId());
        $collectors = $client->collectors()->getAll([
            'datacentre_id' => $router->availabilityZone->datacentre_site_id,
            'is_shared' => true,
        ]);

        if (count($collectors) < 1) {
            Log::info('No Collector found for datacentre', [
                'availability_zone_id' => $router->availabilityZone->id,
                'network_id' => $network->id,
                'router_id' => $router->id,
                'datacentre_site_id' => $router->availabilityZone->datacentre_site_id,
            ]);
            return;
        }

        $ipAddresses = [];
        foreach ($collectors as $collector) {
            $ipAddresses[] = $collector->ipAddress;
        }
        $ipAddresses = implode(',', $ipAddresses);

        foreach (config('network.rule_templates') as $rule) {
            $networkRule = app()->make(NetworkRule::class);
            $networkRule->fill($rule);
            $networkRule->source = $ipAddresses;
            $this->model->networkRules()->save($networkRule);

            foreach ($rule['ports'] as $port) {
                $networkRulePort = app()->make(NetworkRulePort::class);
                $networkRulePort->fill($port);
                $networkRulePort->networkRule()->associate($networkRule);
                $networkRulePort->save();
            }
        }
    }
}
