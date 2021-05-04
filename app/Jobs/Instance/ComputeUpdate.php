<?php

namespace App\Jobs\Instance;

use App\Jobs\Job;
use App\Models\V2\Instance;
use App\Traits\V2\JobModel;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class ComputeUpdate extends Job
{
    use Batchable, JobModel;

    private $model;

    public function __construct(Instance $instance)
    {
        $this->model = $instance;
    }

    public function handle()
    {
        $instanceResponse = $this->model->availabilityZone->kingpinService()->get('/api/v2/vpc/' . $this->model->vpc->id . '/instance/' . $this->model->id);
        $instanceResponseData = json_decode($instanceResponse->getBody()->getContents());

        $currentVCPUCores = $instanceResponseData->numCPU;
        $currentRAMMiB = $instanceResponseData->ramMiB;

        if ($currentVCPUCores == 0 || $currentRAMMiB == 0) {
            throw new \Exception("Unable to determine current vCPU/RAM for instance");
        }


        if ($currentVCPUCores == $this->model->vcpu_cores && $currentRAMMiB == $this->model->ram_capacity) {
            Log::info(get_class($this) . ' : Finished: No changes required', ['id' => $this->model->id]);
            return;
        }

        $reboot = false;

        $ram_limit = (($this->model->platform == 'Windows') ? 16 : 3) * 1024;

        if ($this->model->ram_capacity < $currentRAMMiB) {
            $reboot = true;
        }

        if ($this->model->ram_capacity > $ram_limit && $currentRAMMiB <= $ram_limit) {
            $reboot = true;
        }

        if ($this->model->vcpu_cores < $currentVCPUCores) {
            $reboot = true;
        }

        Log::info(
            'Resizing compute resources on instance ' . $this->model->id,
            [
                'ramMiB' => $this->model->ram_capacity,
                'numCPU' => $this->model->vcpu_cores,
                'guestShutdown' => $reboot
            ]
        );

        $this->model->availabilityZone->kingpinService()->put(
            '/api/v2/vpc/' . $this->model->vpc->id . '/instance/' . $this->model->id . '/resize',
            [
                'json' => [
                    'ramMiB' => $this->model->ram_capacity,
                    'numCPU' => $this->model->vcpu_cores,
                    'guestShutdown' => $reboot
                ],
            ]
        );
    }
}
