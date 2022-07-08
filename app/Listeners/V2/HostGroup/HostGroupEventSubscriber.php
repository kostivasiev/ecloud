<?php

namespace App\Listeners\V2\HostGroup;

use App\Models\V2\Instance;
use App\Models\V2\ResourceTier;
use App\Support\Sync;
use Illuminate\Support\Facades\Log;

class HostGroupEventSubscriber
{
//    protected Task $event;
//    protected Model $resource;

    /**
     * @param $event
     * @return void
     */
    public function handleTaskCreatedEvent($event) {
        $this->event = $event->model;
        $resource = $event->model->resource;

        if ($event->model->name == Sync::TASK_NAME_UPDATE) {
            if ($resource instanceof Instance && empty($resource->host_group_id)) {
                $this->assignToInstance($resource);
            }
        }
    }

    protected function assignToInstance(Instance $instance): bool
    {
        $resourceTier = $this->getResourceTier($instance);

        // Get the least utilised host group
        $hostGroup = $resourceTier->getDefaultHostGroup();

        Log::info($this::class . ': Assigning host group ' . $hostGroup->id . ' to instance '  .$instance->id);

        $instance->hostGroup()->associate($hostGroup);

        dd($hostGroup);
        // Get the host group from the resource tier with the least utilised capacity




        return true;
    }

    /**
     * Determine the resource tier to use for an instance
     * @param Instance $instance
     * @return ResourceTier
     */
    protected function getResourceTier(Instance $instance): ResourceTier
    {
        if (!empty($instance->deploy_data['resource_tier_id'])) {
            return ResourceTier::findOrFail($instance->deploy_data['resource_tier_id']);
        }

        Log::info($this::class . ': No resource_tier_id specified for instance ' . $instance->id .
            ', using default for availability zone.', [
                'availability_zone_id' => $instance->availabilityZone->id,
                'resource_tier_id' => $instance->availabilityZone->resourceTier->id
            ]
        );
        return $instance->availabilityZone->resourceTier;
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @param  \Illuminate\Events\Dispatcher $events
     * @return array
     */
    public function subscribe($events)
    {
        return [
            \App\Events\V2\Task\Created::class => 'handleTaskCreatedEvent',
        ];
    }
}
