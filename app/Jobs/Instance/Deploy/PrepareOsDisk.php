<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\Job;
use App\Models\V2\Instance;
use App\Models\V2\Volume;
use App\Models\V2\Vpc;
use Illuminate\Support\Facades\Log;

class PrepareOsDisk extends Job
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['data' => $this->data]);

        $instance = Instance::findOrFail($this->data['instance_id']);

        // Resize primary volume - Single volume for MVP
        $volume = $instance->volumes->first();
        $volume->capacity = $this->data['volume_capacity'];
        $volume->save();

        Log::info(get_class($this) . ' : Finished', ['data' => $this->data]);
    }
}
