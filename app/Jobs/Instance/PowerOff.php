<?php

namespace App\Jobs\Instance;

use App\Jobs\Job;
use App\Models\V2\Instance;
use App\Models\V2\Vpc;
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
        Log::debug(get_class($this) . ' : Started', ['id' => $this->instance->id]);

        $response = $this->instance->availabilityZone->kingpinService()->delete(
            '/api/v2/vpc/' . $this->instance->vpc->id . '/instance/' . $this->instance->id . '/power'
        );

        // Catch already deleted
        $responseJson = json_decode($response->getBody()->getContents());
        if (isset($responseJson->ExceptionType) && $responseJson->ExceptionType == 'UKFast.VimLibrary.Exception.EntityNotFoundException') {
            Log::warning('Attempted to power off, but entity was not found, skipping.');
            return;
        }

        Log::debug(get_class($this) . ' : Finished', ['id' => $this->instance->id]);
    }
}
