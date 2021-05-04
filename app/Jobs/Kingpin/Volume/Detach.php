<?php

namespace App\Jobs\Kingpin\Volume;

use App\Jobs\Job;
use App\Models\V2\Instance;
use App\Models\V2\Volume;
use GuzzleHttp\Exception\ServerException;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class Detach extends Job
{
    use Batchable;

    private Volume $volume;
    private Instance $instance;

    public function __construct(Volume $volume, Instance $instance)
    {
        $this->volume = $volume;
        $this->instance = $instance;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started');

        $this->instance->availabilityZone->kingpinService()
                ->post('/api/v2/vpc/' . $this->instance->vpc_id . '/instance/' . $this->instance->id . '/volume/' . $this->volume->vmware_uuid . '/detach');

        $this->instance->volumes()->detach($this->volume);

        Log::debug('Volume ' . $this->volume->id . ' has been detached from instance ' . $this->instance->id);

        Log::info(get_class($this) . ' : Finished');
    }

    public function failed($exception)
    {
        $this->volume->setSyncFailureReason($exception->getMessage());
    }
}
