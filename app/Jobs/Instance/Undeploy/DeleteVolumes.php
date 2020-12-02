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
            $instance->volumes()->detach($volume);
            if ($volume->instances()->count() == 0) {
                $volume->delete();
            }
        });
        Log::info(get_class($this) . ' : Finished', ['data' => $this->data]);
    }
}
