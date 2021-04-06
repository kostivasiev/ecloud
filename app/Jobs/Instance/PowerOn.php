<?php

namespace App\Jobs\Instance;

use App\Jobs\Job;
use App\Models\V2\Instance;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class PowerOn extends Job
{
    use Batchable;

    private $instance;

    public function __construct(Instance $instance)
    {
        $this->instance = $instance;
    }

    /**
     * @see https://gitlab.devops.ukfast.co.uk/ukfast/api.ukfast/ecloud/-/issues/328
     */
    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->instance->id]);

        $this->instance->availabilityZone->kingpinService()->post(
            '/api/v2/vpc/' . $this->instance->vpc->id . '/instance/' . $this->instance->id . '/power'
        );

        Log::info(get_class($this) . ' : Finished', ['id' => $this->instance->id]);
    }
}
