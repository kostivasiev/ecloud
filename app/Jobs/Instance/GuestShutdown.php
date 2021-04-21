<?php

namespace App\Jobs\Instance;

use App\Jobs\Job;
use App\Models\V2\Instance;
use Illuminate\Support\Facades\Log;

class GuestShutdown extends Job
{
    private $instance;

    public function __construct(Instance $instance)
    {
        $this->instance = $instance;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->instance->id]);

        $this->instance->availabilityZone->kingpinService()->put(
            '/api/v2/vpc/' . $this->instance->vpc->id . '/instance/' . $this->instance->id . '/power/guest/shutdown'
        );

        Log::info(get_class($this) . ' : Finished', ['id' => $this->instance->id]);
    }
}
