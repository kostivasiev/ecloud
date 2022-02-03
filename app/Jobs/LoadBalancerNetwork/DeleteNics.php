<?php

namespace App\Jobs\LoadBalancerNetwork;

use App\Jobs\TaskJob;
use App\Models\V2\IpAddress;
use App\Models\V2\Nic;
use App\Support\Sync;
use App\Traits\V2\TaskJobs\AwaitTask;
use GuzzleHttp\Exception\RequestException;

class DeleteNics extends TaskJob
{
    use AwaitTask;

    /**
     * Delete NICs from the load balancer notes for this network
     *
     * @see https://mgmt-20.ecloud-service.ukfast.co.uk:8443/swagger/ui/index#/VPC_Instance_v2/VPC_Instance_v2_RemoveNIC
     * @return void
     */
    public function handle()
    {
        $loadBalancerNetwork = $this->task->resource;

        $taskIdsKey = 'task.' . Sync::TASK_NAME_DELETE. '.ids';

        if (empty($this->task->data[$taskIdsKey])) {
            $ids = [];

            $nics = Nic::where('network_id', '=', $loadBalancerNetwork->network->id)
                ->whereHas('instance.loadBalancerNode', function ($query) use ($loadBalancerNetwork) {
                    $query->where('load_balancer_id', '=', $loadBalancerNetwork->loadbalancer->id);
                })->get();

                foreach ($nics as $nic) {
                    if ($nic->ipAddresses()->withType(IpAddress::TYPE_CLUSTER)->exists()) {
                        $this->fail(new \Exception('Failed to delete NIC ' . $nic->id . ', ' . IpAddress::TYPE_CLUSTER . ' IP detected'));
                        return;
                    }

                    $instance = $nic->instance;

                    try {
                        $instance->availabilityZone->kingpinService()->delete(
                            '/api/v2/vpc/' . $instance->vpc->id .
                            '/instance/' . $instance->id .
                            '/nic/' . $nic->mac_address
                        );
                        $this->info('NIC ' . $nic->id . ' was removed from instance ' . $instance->id);
                    } catch (RequestException $exception) {
                        if ($exception->getCode() != 404) {
                            throw $exception;
                        }
                        $this->info('NIC was not found on the instance, nothing to do, skipping.');
                    }

                    $this->info('Deleting NIC ' . $nic->id);
                    $task = $nic->syncDelete();

                    $ids[] = $task->id;
                }

            $this->task->updateData($taskIdsKey, $ids);
        }

        if (isset($this->task->data[$taskIdsKey])) {
            $this->awaitTasks($this->task->data[$taskIdsKey]);
        }
    }
}
