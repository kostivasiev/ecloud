<?php

namespace App\Listeners\V2\Instance;

use App\Events\V2\Instance\Updated;
use App\Models\V2\HostGroup;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class HostGroupChange implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * @param Updated $event
     * @return void
     * @throws \Exception
     */
    public function handle(Updated $event)
    {
        Log::info(get_class($this) . ' : Started', ['id' => $event->model->id]);

        $instance = $event->model;

        if ($event->original['host_group_id'] == $instance->host_group_id) {
            Log::info(get_class($this) . ' : Finished: No changes required', ['id' => $event->model->id]);
            $instance->setSyncCompleted();  // TODO :- Move this once the new Sync logic is in
            return;
        }

        $originalHostGroup = HostGroup::findOrFail($event->original['host_group_id']);
        $newHostGroup = HostGroup::findOrFail($instance->host_group_id);
        $cyclePower = ($originalHostGroup->hostSpec->id != $newHostGroup->hostSpec->id);

        // TODO :- Logic to end billing when switching from shared to dedicated, not MVP

        if ($cyclePower) {
            // Power off
            $instance->availabilityZone->kingpinService()
                ->delete('/api/v2/vpc/' . $instance->vpc_id . '/instance/' . $instance->id . '/power');
        }

        $instance->availabilityZone->kingpinService()->post(
            '/api/v2/vpc/' . $instance->vpc_id . '/instance/' . $instance->id . '/reschedule',
            [
                'json' => [
                    'hostGroupId' => $instance->host_group_id,
                ],
            ]
        );

        if ($cyclePower) {
            // Power on
            $instance->availabilityZone->kingpinService()
                ->post('/api/v2/vpc/' . $instance->vpc_id . '/instance/' . $instance->id . '/power');
        }

        $instance->setSyncCompleted();

        Log::info(get_class($this) . ' : Finished', ['id' => $event->model->id]);
    }
}
