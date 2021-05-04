<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\Job;
use App\Models\V2\Instance;
use App\Traits\V2\JobModel;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class DeployCompleted extends Job
{
    use Batchable, JobModel;

    private $model;

    public function __construct(Instance $instance)
    {
        $this->model = $instance;
    }

    /**
     * @throws \Exception
     */
    public function handle()
    {
        $this->model->deployed = true;
        //this->instance->deploy_data = '';
        $this->model->saveQuietly();
    }
}
