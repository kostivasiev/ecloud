<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\TaskJob;
use App\Models\V2\Instance;
use App\Models\V2\Task;
use App\Models\V2\Vpc;
use Illuminate\Support\Facades\Log;

class WaitOsCustomisation extends TaskJob
{
    const RETRY_ATTEMPTS = 360;
    const RETRY_DELAY = 5; // Retry every 5 seconds for 20 minutes
    public $tries = 500;
    private $data;

    public function __construct(Task $task, $data)
    {
        parent::__construct($task);

        $this->data = $data;
    }

    /**
     * @see https://gitlab.devops.ukfast.co.uk/ukfast/api.ukfast/ecloud/-/issues/329
     */
    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['data' => $this->data]);

        $instance = Instance::findOrFail($this->data['instance_id']);
        $vpc = Vpc::findOrFail($this->data['vpc_id']);
        $response = $instance->availabilityZone->kingpinService()->get(
            '/api/v2/vpc/' . $vpc->id . '/instance/' . $instance->id . '/oscustomization/status'
        );

        $data = json_decode($response->getBody()->getContents());
        if (!$data) {
            $message = 'WaitOsCustomisation failed for ' . $instance->id . ', could not decode response';
            Log::error($message, ['response' => $response]);
            $this->fail(new \Exception($message));
            return;
        }

        if ($data->status === 'Failed') {
            $message = 'WaitOsCustomisation failed for ' . $instance->id;
            Log::error($message, ['data' => $data]);
            $this->fail(new \Exception($message));
            return;
        }

        if ($data->status !== 'Succeeded') {
            if ($this->attempts() <= static::RETRY_ATTEMPTS) {
                $this->release(static::RETRY_DELAY);
                Log::info(
                    'Check for WaitOsCustomisation for ' . $instance->id . ' returned "' .
                    $data->status . '", retrying in ' . static::RETRY_DELAY . ' seconds'
                );
                return;
            } else {
                $this->fail(new \Exception('Timed out on WaitOsCustomisation for ' . $instance->id));
                return;
            }
        }

        Log::info(get_class($this) . ' : Finished', ['data' => $this->data]);
    }
}
