<?php

namespace App\Listeners\V2\HostGroup;

use App\Models\V2\HostGroup;
use App\Models\V2\Instance;
use App\Models\V2\ResourceTier;
use App\Models\V2\Task;
use App\Support\Sync;
use App\Tasks\Instance\Migrate;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Log;

class HostGroupEventSubscriber implements ShouldQueue
{
    public function handleTaskCreatedEvent($event)
    {
        $task = $event->model;
        $resource = $task->resource;
        if ($task->name == Sync::TASK_NAME_UPDATE) {
            if ($resource instanceof Instance && empty($resource->host_group_id)) {
                $this->assignToInstance($resource);
            }
        }

        if ($task->name == Migrate::TASK_NAME && empty($task->data['host_group_id'])) {
            $this->assignToMigrateTask($task);
        }
    }

    public function handleMigratedEvent($event): void
    {
        $this->associate($event->instance, $event->hostGroup);
    }

    protected function assignToMigrateTask(Task $task)
    {
        $resourceTier = ResourceTier::find(
            $task->data['resource_tier_id'] ??
            $task->resource->availabilityZone->resource_tier_id
        );

        $hostGroup = $resourceTier->getDefaultHostGroup();

        if (empty($hostGroup)) {
            Log::error($this::class . ': Failed to assign host group to migrate task ' . $task->id .
                ' - There was an error retrieving the default host group for the resource tier.');
            return;
        }

        $task->updateData('host_group_id', $hostGroup->id);
    }

    protected function assignToInstance(Instance $instance): void
    {
        $resourceTier = $this->getResourceTier($instance);

        // Get the least utilised host group
        $hostGroup = $resourceTier->getDefaultHostGroup();

        if (empty($hostGroup)) {
            Log::error($this::class . ': Failed to assign host group to instance ' . $instance->id .
                ' - There was an error retrieving the default host group for the resource tier.');
            return;
        }

        $this->associate($instance, $hostGroup);
    }

    protected function associate(Instance $instance, HostGroup $hostGroup)
    {
        Log::info($this::class . ': Assigning host group ' . $hostGroup->id . ' to instance '  . $instance->id);
        $instance->hostGroup()->associate($hostGroup)->save();
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
        ]);
        return $instance->availabilityZone->resourceTier;
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
            \App\Events\V2\Instance\Migrated::class => 'handleMigratedEvent',
        ];
    }
}
