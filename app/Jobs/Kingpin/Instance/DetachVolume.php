<?php

namespace App\Jobs\Kingpin\Instance;

use App\Jobs\Job;
use App\Models\V2\Instance;
use App\Models\V2\Volume;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class DetachVolume extends Job
{
    use Batchable, LoggableModelJob;

    private Instance $instance;
    private Volume $volume;

    public function __construct(Instance $instance, Volume $volume)
    {
        $this->instance = $instance;
        $this->volume = $volume;
    }

    public function resolveModelId()
    {
        return $this->instance->id;
    }

    public function handle()
    {
        $this->instance->availabilityZone->kingpinService()
            ->post('/api/v2/vpc/' . $this->instance->vpc_id . '/instance/' . $this->instance->id . '/volume/' . $this->volume->vmware_uuid . '/detach');

        $this->instance->volumes()->detach($this->volume);

        Log::debug('Volume ' . $this->volume->id . ' has been detached from instance ' . $this->instance->id);
    }
}
