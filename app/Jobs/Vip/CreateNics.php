<?php

namespace App\Jobs\Vip;

use App\Jobs\TaskJob;
use App\Jobs\Tasks\Nic\AssociateIp;
use App\Models\V2\Nic;

class CreateNics extends TaskJob
{
    /**
     * Create a new nic on the lb's instances (if the supplied network doesn't have a NIC already)
     * @return void
     */
    public function handle()
    {
        $vip = $this->task->resource;
        $network = $vip->network;
        $loadBalancer = $vip->loadBalancer;

        $loadBalancer->instances->each(function ($instance) use ($vip) {


            $instance->nics()->firstOrNew(
                ['network_id' => $vip->network_id],
                [
                    'name' => 'keepalived',
                    'host' => null,
                    'password' => $passwordService->generate(8),
                    'port' => null,
                    'is_hidden' => true,
                ]
            );

        });






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
