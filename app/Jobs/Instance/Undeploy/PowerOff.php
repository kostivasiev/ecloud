<?php

namespace App\Jobs\Instance\Undeploy;

use App\Jobs\Job;
use App\Models\V2\Instance;
use App\Models\V2\Vpc;
use Illuminate\Support\Facades\Log;

class PowerOff extends Job
{
    private $data;
    private $setSyncCompleted;

    public function __construct($data, $setSyncCompleted = true)
    {
        $this->data = $data;
        $this->setSyncCompleted = $setSyncCompleted;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['data' => $this->data]);

        $instance = Instance::withTrashed()->findOrFail($this->data['instance_id']);
        $vpc = Vpc::findOrFail($this->data['vpc_id']);
        $response = $instance->availabilityZone->kingpinService()->delete(
            '/api/v2/vpc/' . $vpc->id . '/instance/' . $instance->id . '/power'
        );

        // Catch already deleted
        $responseJson = json_decode($response->getBody()->getContents());
        if (isset($responseJson->ExceptionType) && $responseJson->ExceptionType == 'UKFast.VimLibrary.Exception.EntityNotFoundException') {
            Log::info('Attempted to power off, but entity was not found, skipping.');
            return;
        }

        if ($this->setSyncCompleted) {
            $instance->setSyncCompleted();
        }

        Log::info(get_class($this) . ' : Finished', ['data' => $this->data]);
    }
}
