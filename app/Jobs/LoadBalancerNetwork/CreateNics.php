<?php

namespace App\Jobs\LoadBalancerNetwork;

use App\Jobs\TaskJob;
use App\Jobs\Tasks\Nic\AssociateIp;
use App\Models\V2\Nic;

class CreateNics extends TaskJob
{
    /**
     * Create a new NIC on the lb's instances if the supplied network doesn't have a NIC already
     * @return void
     */
    public function handle()
    {
        $loadBalancerNetwork = $this->task->resource;

        $network = $loadBalancerNetwork->network;

        $loadBalancer = $loadBalancerNetwork->loadBalancer;


        $nic = new Nic($request->only([

        ]));




        /api/v2/vpc/{vpcId}/instance/{instanceId}/nic

        $nic = $loadBalancer->nodes->each(function ($node) use ($loadBalancerNetwork) {
            $node->nics()->firstOrNew(
                ['network_id' => $loadBalancerNetwork->network_id],
                [
                    'name',
                    'mac_address',
                    'instance_id',
                    'network_id',
                ]
            );

        });

        $task = $nic->syncSave();




        Nic::where('network_id', '=', $vip->network_id)
            ->whereHas('instance.loadbalancer', function ($query) use ($vip) {
                $query->where('id', '=', $vip->loadbalancer->id);
            })->each(function ($nic) use ($vip, &$taskIds) {
                if (!$nic->ipAddresses()->where('id', $vip->ipAddress->id)->exists()) {
                    $task = $nic->createTaskWithLock(AssociateIp::$name, AssociateIp::class, ['ip_address_id' => $vip->ipAddress->id]);
                    $taskIds[] = $task->id;
                }
            });

    }
}
