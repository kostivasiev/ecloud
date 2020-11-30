<?php

namespace App\Jobs\Instance\Undeploy;

use App\Jobs\Job;
use App\Models\V2\Instance;
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
        $instance->volumes()->whereNotNull('vmware_uuid')->each(function ($volume) use ($instance) {
            // count the number of instances that have this volume attached
            $volume = $volume->withCount(['instances'])->first();
            // if only zero/one instances have this volume attached then detach it and delete it
            if ($volume->instances_count <= 1) {
                $instance->volumes()->detach($volume);
                $volume->delete();
            }
        });
        Log::info(get_class($this) . ' : Finished', ['data' => $this->data]);
    }
}
