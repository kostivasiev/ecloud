<?php

namespace App\Jobs\Sync\Host;

use App\Jobs\Artisan\Host\AddToHostSet;
use App\Jobs\Artisan\Host\Deploy;
use App\Jobs\Conjurer\Host\CreateAutoDeployRule;
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

        $jobs = [
            new CreateProfile($this->model),
            new CreateAutoDeployRule($this->model),
            new Deploy($this->model),
            new AddToHostSet($this->model),
            new PowerOn($this->model),
            new CheckOnline($this->model),
            // TODO: Add host into nsx (nsx api, this may not be required if the cluster does this) #626
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
