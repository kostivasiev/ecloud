<?php

namespace App\Jobs\LoadBalancer;

use App\Jobs\TaskJob;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use UKFast\Admin\Loadbalancers\AdminClient;

class DeleteAntiAffinity extends TaskJob
{
    public function handle()
    {
        $loadBalancer = $this->task->resource;

        if ($loadBalancer->loadBalancerNodes->count() <= 1) {
            $this->info("Skipping, LB not HA");
            return;
        }

        $loadBalancerNodeInstances = $loadBalancer->loadBalancerNodes->pluck('instance');

        foreach ($loadBalancerNodeInstances as $instance) {
            if ($instance !== null) {
                $loadBalancerNodeInstanceId = $instance->id;
                break;
            }
        }

        if (empty($loadBalancerNodeInstanceId)) {
            $this->info("Skipping, LB no load balancer node instances.");
            return;
        }

        // First, we'll retrieve the host group ID for the first instance
        try {
            $response = $loadBalancer->availabilityZone->kingpinService()->get(
                '/api/v2/vpc/' . $loadBalancer->vpc->id .
                '/instance/' . $loadBalancerNodeInstanceId
            );
        } catch (ClientException $e) {
            if ($e->hasResponse() && $e->getResponse()->getStatusCode() == '404') {
                $this->info("Skipping, instance not found");
                return;
            }

            throw $e;
        }
        $response = json_decode($response->getBody()->getContents());
        $hostGroupId = $response->hostGroupID;

        // Next, we'll check to see whether constraint exists
        $response = $loadBalancer->availabilityZone->kingpinService()->get(
            '/api/v2/hostgroup/' . $hostGroupId . '/constraint'
        );
        $response = json_decode($response->getBody()->getContents());

        $exists = false;
        foreach ($response as $constraint) {
            if ($constraint->ruleName == $loadBalancer->id) {
                $exists = true;
                break;
            }
        }

        if (!$exists) {
            $this->info('Skipping removal, doesn\'t exist');
            return;
        }

        // Finally, we'll delete the rule
        $loadBalancer->availabilityZone->kingpinService()->delete(
            '/api/v2/hostgroup/' . $hostGroupId . '/constraint/' . $loadBalancer->id
        );
    }
}
