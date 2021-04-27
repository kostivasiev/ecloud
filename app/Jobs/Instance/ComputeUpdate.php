<?php

namespace App\Jobs\Instance;

use App\Jobs\Job;
use App\Models\V2\Instance;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class ComputeUpdate extends Job
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

        $instanceResponse = $this->instance->availabilityZone->kingpinService()->get('/api/v2/vpc/' . $this->instance->vpc->id . '/instance/' . $this->instance->id);
        $instanceResponseData = json_decode($instanceResponse->getBody()->getContents());

        $currentVCPUCores = $instanceResponseData->numCPU;
        $currentRAMMiB = $instanceResponseData->ramMiB;

        if ($currentVCPUCores == 0 || $currentRAMMiB == 0) {
            throw new \Exception("Unable to determine current vCPU/RAM for instance");
        }


        if ($currentVCPUCores == $this->instance->vcpu_cores && $currentRAMMiB == $this->instance->ram_capacity) {
            Log::info(get_class($this) . ' : Finished: No changes required', ['id' => $this->instance->id]);
            return;
        }

        $reboot = false;

        $ram_limit = (($this->instance->platform == 'Windows') ? 16 : 3) * 1024;

        if ($this->instance->ram_capacity < $currentRAMMiB) {
            $reboot = true;
        }

        if ($this->instance->ram_capacity > $ram_limit && $currentRAMMiB <= $ram_limit) {
            $reboot = true;
        }

        if ($this->instance->vcpu_cores < $currentVCPUCores) {
            $reboot = true;
        }

        Log::info(
            'Resizing compute resources on instance ' . $this->instance->id,
            [
                'ramMiB' => $this->instance->ram_capacity,
                'numCPU' => $this->instance->vcpu_cores,
                'guestShutdown' => $reboot
            ]
        );

        $this->instance->availabilityZone->kingpinService()->put(
            '/api/v2/vpc/' . $this->instance->vpc->id . '/instance/' . $this->instance->id . '/resize',
            [
                'json' => [
                    'ramMiB' => $this->instance->ram_capacity,
                    'numCPU' => $this->instance->vcpu_cores,
                    'guestShutdown' => $reboot
                ],
            ]
        );

        Log::info(get_class($this) . ' : Finished', ['id' => $this->instance->id]);
    }
}
