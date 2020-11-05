<?php

namespace App\Jobs\Instance\Undeploy;

use App\Jobs\Job;
use App\Models\V2\Instance;
use Illuminate\Support\Facades\Log;

class Undeploy extends Job
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
        $instance->availabilityZone
            ->kingpinService()
            ->delete('/api/v2/vpc/' . $instance->vpc_id . '/instance/' . $instance->getKey());

        Log::info(get_class($this) . ' : Finished', ['data' => $this->data]);
    }
}
