<?php

namespace App\Jobs\Instance\Undeploy;

use App\Jobs\Job;
use App\Models\V2\Instance;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class Undeploy extends Job
{
    use Batchable;

    private $instance;

    public function __construct(Instance $instance)
    {
        $this->instance = $instance;
    }

    public function handle()
    {
        Log::debug(get_class($this) . ' : Started', ['id' => $this->instance->id]);

        $this->instance->availabilityZone
            ->kingpinService()
            ->delete('/api/v2/vpc/' . $this->instance->vpc->id . '/instance/' . $this->instance->id);

        Log::debug(get_class($this) . ' : Finished', ['id' => $this->instance->id]);
    }
}
