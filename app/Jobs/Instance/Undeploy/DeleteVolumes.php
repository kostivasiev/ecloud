<?php

namespace App\Jobs\Instance\Undeploy;

use App\Jobs\Job;
use App\Models\V2\Instance;
use App\Models\V2\Vpc;
use Illuminate\Support\Facades\Log;

class DeleteVolumes extends Job
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['data' => $this->data]);

        $instance = Instance::withTrashed()->findOrFail($this->data['instance_id']);
        $vpc = Vpc::findOrFail($this->data['vpc_id']);
        $logMessage = 'DeleteVolumes for instance ' . $instance->getKey() . ': ';

        $instance->volumes()->each(function ($volume) use ($instance, $vpc) {
            // If volume is only used in this instance then delete
            if ($volume->instances()->count() == 0) {
                Log::info('Deleting volume: ' . $volume->getKey());
                // Send delete request to kingpin
                $instance->availabilityZone->kingpinService()->delete(
                    '/api/v1/vpc/' . $vpc->id . '/instance/' . $instance->id . '/volume/' . $volume->vmware_uuid
                );
                Log::info('Volume ' . $volume->getKey() . ' (' . $volume->vmware_uuid . ') deleted.');
                $volume->instances()->detach($instance);
                $volume->delete();
            }
        });

        Log::info(get_class($this) . ' : Finished', ['data' => $this->data]);
    }
}
