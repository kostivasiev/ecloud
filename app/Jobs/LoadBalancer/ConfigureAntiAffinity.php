<?php

namespace App\Jobs\LoadBalancer;

use App\Jobs\TaskJob;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use UKFast\Admin\Loadbalancers\AdminClient;

class ConfigureAntiAffinity extends TaskJob
{
    public function handle()
    {
        $loadBalancer = $this->task->resource;

        if ($loadBalancer->loadBalancerNodes->count() <= 1) {
            $this->info("Skipping, LB not HA");
            return;
        }

        $loadBalancerNodeInstances = $loadBalancer->loadBalancerNodes->pluck('instance');

        // First, we'll retrieve the host group ID for the first instance
        $response = $loadBalancer->availabilityZone->kingpinService()->get(
            '/api/v2/vpc/' . $loadBalancer->vpc->id .
            '/instance/' . $loadBalancerNodeInstances->first()->id
        );
        $response = json_decode($response->getBody()->getContents());
        $hostGroupId = $response->hostGroupID;

        // Next, we'll check to see whether constraint exists
        $response = $loadBalancer->availabilityZone->kingpinService()->get(
            '/api/v2/hostgroup/' . $hostGroupId . '/constraint'
        );
        $response = json_decode($response->getBody()->getContents());

        foreach ($response as $constraint) {
            if ($constraint->ruleName == $loadBalancer->id) {
                $this->info('Constraint already exists');
                return;
            }
        }

        // Finally, we'll create the rule as it doesn't exist
        $loadBalancer->availabilityZone->kingpinService()->post(
            '/api/v2/hostgroup/' . $hostGroupId . '/constraint/instance/separate',
            [
                'json' => [
                    'ruleName' => $loadBalancer->id,
                    'vpcId' => $loadBalancer->vpc->id,
                    'instanceIds' => $loadBalancerNodeInstances->pluck('id')->toArray(),
                ]
            ]
        );
    }
}
