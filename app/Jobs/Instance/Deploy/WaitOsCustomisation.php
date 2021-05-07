<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\Job;
use App\Models\V2\Instance;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class WaitOsCustomisation extends Job
{
    use Batchable, LoggableModelJob;

    const RETRY_ATTEMPTS = 360;
    const RETRY_DELAY = 5; // Retry every 5 seconds for 20 minutes
    public $tries = 500;
    private $model;

    public function __construct(Instance $instance)
    {
        $this->model = $instance;
    }

    /**
     * @see https://gitlab.devops.ukfast.co.uk/ukfast/api.ukfast/ecloud/-/issues/329
     */
    public function handle()
    {
        $response = $this->model->availabilityZone->kingpinService()->get(
            '/api/v2/vpc/' . $this->model->vpc->id . '/instance/' . $this->model->id . '/oscustomization/status'
        );

        $data = json_decode($response->getBody()->getContents());
        if (!$data) {
            $message = 'WaitOsCustomisation failed for ' . $this->model->id . ', could not decode response';
            Log::error($message, ['response' => $response]);
            $this->fail(new \Exception($message));
            return;
        }

        if ($data->status === 'Failed') {
            $message = 'WaitOsCustomisation failed for ' . $this->model->id;
            Log::error($message, ['data' => $data]);
            $this->fail(new \Exception($message));
            return;
        }

        if ($data->status !== 'Succeeded') {
            if ($this->attempts() <= static::RETRY_ATTEMPTS) {
                $this->release(static::RETRY_DELAY);
                Log::info(
                    'Check for WaitOsCustomisation for ' . $this->model->id . ' returned "' .
                    $data->status . '", retrying in ' . static::RETRY_DELAY . ' seconds'
                );
                return;
            } else {
                $this->fail(new \Exception('Timed out on WaitOsCustomisation for ' . $this->model->id));
                return;
            }
        }
    }
}
