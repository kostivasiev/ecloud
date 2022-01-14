<?php

namespace App\Jobs\LoadBalancerNetwork;

use App\Jobs\TaskJob;
use App\Traits\V2\TaskJobs\AwaitResources;

class CreateNics extends TaskJob
{
    use AwaitResources;

    /**
     * Create a new NIC on the lb's instances if the supplied network doesn't have a NIC already
     * @return void
     */
    public function handle()
    {
        $loadBalancerNetwork = $this->task->resource;

        $loadBalancer = $loadBalancerNetwork->loadBalancer;

        if (empty($this->task->data['nic_ids'])) {
            $data = $this->task->data;
            $data['nic_ids'] = [];

            $loadBalancer->instances->each(function ($instance) use ($loadBalancerNetwork, &$data) {
                $nic = $instance->nics()->firstOrNew(
                    ['network_id' => $loadBalancerNetwork->network_id],
                    [
                        'instance_id' => $instance->id,
                    ]
                );

                if ($nic->isDirty()) {
                    $response = $instance->availabilityZone->kingpinService()->post(
                        '/api/v2/vpc/' . $instance->vpc->id .
                        '/instance/' . $instance->id .
                        '/nic',
                        [
                            'json' => [
                                'networkId' => $loadBalancerNetwork->network_id,
                            ],
                        ]
                    );

                    $nic->mac_address = json_decode($response->getBody()->getContents())->macAddress;
                    $nic->syncSave();

                    $this->info('Created new NIC ' . $nic->id . ' on load balancer node ' . $instance->id . ' for network ' . $loadBalancerNetwork->network_id);

                    $data['nic_ids'][] = $nic->id;
                }
            });

            $this->task->setAttribute('data', $data)->saveQuietly();
        }

        if (isset($this->task->data['nic_ids'])) {
            $this->awaitSyncableResources($this->task->data['nic_ids']);
        }
    }
}
