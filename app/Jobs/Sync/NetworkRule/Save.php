<?php

namespace App\Jobs\Sync\NetworkRule;

use App\Jobs\Job;
use App\Jobs\Nsx\NetworkRule\Deploy;
use App\Jobs\Nsx\NetworkRule\DeployCheck;
use App\Models\V2\NetworkRule;
use Illuminate\Support\Facades\Log;

class Save extends Job
{
    private $model;

    public function __construct(NetworkRule $model)
    {
        $this->model = $model;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->model->id]);

        $jobs = [
            new Deploy($this->model),
            new DeployCheck($this->model),
        ];

        dispatch(array_shift($jobs)->chain($jobs));

        Log::info(get_class($this) . ' : Finished', ['id' => $this->model->id]);
    }
}
