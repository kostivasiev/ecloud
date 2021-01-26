<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\Job;
use App\Models\V2\Instance;
use Illuminate\Support\Facades\Log;

class AssignVolumeIops extends Job
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['data' => $this->data]);

        // Finally set the iops, we can rely on the update events to handle the billing lines
        $instance = Instance::findOrFail($this->data['instance_id']);
        $volume = $instance->volumes->first();
        $volume->iops = $this->data['iops'];
        $volume->save();
        Log::info('Volume ' . $volume->getKey() . ' iops set to ' . $volume->iops);

        Log::info(get_class($this) . ' : Finished', ['data' => $this->data]);
    }
}
