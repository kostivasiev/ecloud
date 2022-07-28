<?php

namespace App\Jobs\LoadBalancerNetwork;

use App\Jobs\TaskJob;
use App\Support\Sync;

class AwaitNicDeletion extends TaskJob
{
    public $tries = 60;

    public $backoff = 5;

    public function handle()
    {
        $loadBalancerNetwork = $this->task->resource;

        if ($loadBalancerNetwork->getNodeNics()->count() > 0) {
            foreach ($loadBalancerNetwork->getNodeNics() as $nic) {
                if ($nic->sync->status = Sync::STATUS_FAILED && $nic->sync->type == Sync::TYPE_DELETE) {
                    $this->fail(new \Exception('NIC ' . $nic->id . 'failed to delete'));
                    return;
                }
            }
            $this->info('Awaiting NIC deletion from nodes for load balancer network ' . $loadBalancerNetwork->id);
            $this->release($this->backoff);
            return;
        }

        $this->info('NIC deletion from nodes for load balancer network ' . $loadBalancerNetwork->id . ' was successful');
    }
}
