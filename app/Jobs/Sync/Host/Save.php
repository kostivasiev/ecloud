<?php

namespace App\Jobs\Sync\Host;

use App\Jobs\Artisan\Host\AddToHostSet;
use App\Jobs\Artisan\Host\Deploy;
use App\Jobs\Conjurer\Host\CheckAvailableCompute;
use App\Jobs\Conjurer\Host\CreateAutoDeployRule;
use App\Jobs\Conjurer\Host\CreateLanPolicy;
use App\Jobs\Conjurer\Host\CreateProfile;
use App\Jobs\Conjurer\Host\PowerOn;
use App\Jobs\Job;
use App\Jobs\Kingpin\Host\CheckOnline;
use App\Models\V2\Host;
use Illuminate\Support\Facades\Log;

class Save extends Job
{
    private $model;

    public function __construct(Host $model)
    {
        $this->model = $model;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->model->id]);

        // Todo: check if the host already exists /api/v2/compute/{computeName}/vpc/{vpcId}/host/{hostId}


        $jobs = [
            new CreateLanPolicy($this->model),
            new CheckAvailableCompute($this->model),
            new CreateProfile($this->model),

            new CreateAutoDeployRule($this->model),
            new Deploy($this->model),
            new AddToHostSet($this->model),
            new PowerOn($this->model),
            new CheckOnline($this->model),
            new \App\Jobs\Sync\Completed($this->model),
        ];
        dispatch(array_shift($jobs)->chain($jobs));

        Log::info(get_class($this) . ' : Finished', ['id' => $this->model->id]);
    }

    public function failed($exception)
    {
        $this->model->setSyncFailureReason($exception->getMessage());
    }
}
