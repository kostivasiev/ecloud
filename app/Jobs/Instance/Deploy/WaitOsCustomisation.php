<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\Job;
use App\Models\V2\Instance;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class WaitOsCustomisation extends Job
{
    use Batchable;

    const RETRY_ATTEMPTS = 360;
    const RETRY_DELAY = 5; // Retry every 5 seconds for 20 minutes
    public $tries = 500;
    private $instance;

    public function __construct(Instance $instance)
    {
        $this->instance = $instance;
    }

    /**
     * @see https://gitlab.devops.ukfast.co.uk/ukfast/api.ukfast/ecloud/-/issues/329
     */
    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->instance->id]);

        $response = $this->instance->availabilityZone->kingpinService()->get(
            '/api/v2/vpc/' . $this->instance->vpc->id . '/instance/' . $this->instance->id . '/oscustomization/status'
        );

        $data = json_decode($response->getBody()->getContents());
        if (!$data) {
            $message = 'WaitOsCustomisation failed for ' . $this->instance->id . ', could not decode response';
            Log::error($message, ['response' => $response]);
            $this->fail(new \Exception($message));
            return;
        }

        if ($data->status === 'Failed') {
            $message = 'WaitOsCustomisation failed for ' . $this->instance->id;
            Log::error($message, ['data' => $data]);
            $this->fail(new \Exception($message));
            return;
        }

        if ($data->status !== 'Succeeded') {
            if ($this->attempts() <= static::RETRY_ATTEMPTS) {
                $this->release(static::RETRY_DELAY);
                Log::info(
                    'Check for WaitOsCustomisation for ' . $this->instance->id . ' returned "' .
                    $data->status . '", retrying in ' . static::RETRY_DELAY . ' seconds'
                );
                return;
            } else {
                $this->fail(new \Exception('Timed out on WaitOsCustomisation for ' . $this->instance->id));
                return;
            }
        }

        Log::info(get_class($this) . ' : Finished', ['id' => $this->instance->id]);
    }
}
