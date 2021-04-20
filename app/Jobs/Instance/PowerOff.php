<?php

namespace App\Jobs\Instance;

use App\Jobs\Job;
use App\Models\V2\Instance;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class PowerOff extends Job
{
    use Batchable;

    private $instance;

    public function __construct(Instance $instance)
    {
        $this->instance = $instance;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->instance->id]);

        try {
            $this->instance->availabilityZone->kingpinService()->get(
                '/api/v2/vpc/' . $this->instance->vpc->id . '/instance/' . $this->instance->id
            );
        } catch (RequestException $exception) {
            if ($exception->getCode() != 404) {
                throw $exception;
            }
            Log::warning(get_class($this) . ' : Attempted to power off, but instance was not found, skipping.');
            return;
        }

        $this->instance->availabilityZone->kingpinService()->delete(
            '/api/v2/vpc/' . $this->instance->vpc->id . '/instance/' . $this->instance->id . '/power'
        );

        Log::info(get_class($this) . ' : Finished', ['id' => $this->instance->id]);
    }
}
