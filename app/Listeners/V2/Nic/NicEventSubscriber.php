<?php

namespace App\Listeners\V2\Nic;

use App\Models\V2\LoadBalancerNetwork;
use App\Models\V2\Nic;
use App\Models\V2\Task;
use App\Support\Sync;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Log;

class NicEventSubscriber implements ShouldQueue
{
    public function handleTaskCreatedEvent($event)
    {
        $task = $event->model;
        $resource = $task->resource;

        if ($task->name == Sync::TASK_NAME_DELETE) {
            if ($resource instanceof LoadBalancerNetwork) {
                $this->deleteForLoadBalancerNetwork($resource);
            }
        }
    }

    protected function deleteForLoadBalancerNetwork(LoadBalancerNetwork $loadBalancerNetwork)
    {
        $loadBalancerNetwork->getNodeNics()->each(function ($nic) {
            $this->delete($nic);
        });
    }

    /**
     * Sync delete a NIC
     * @param Nic $nic
     * @return Task
     */
    protected function delete(Nic $nic): Task
    {
        Log::info('Deleting Nic ' . $nic->id);
        return $nic->syncDelete();
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @param  Dispatcher $events
     * @return array
     */
    public function subscribe(Dispatcher $events)
    {
        return [
            \App\Events\V2\Task\Created::class => 'handleTaskCreatedEvent',
        ];
    }
}
