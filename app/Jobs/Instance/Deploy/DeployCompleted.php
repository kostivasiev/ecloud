<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\Job;
use App\Models\V2\Instance;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;

class DeployCompleted extends Job
{
    use Batchable, LoggableModelJob;

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

        $this->model->credentials()->where('is_hidden', true)->delete();
        $this->model->saveQuietly();
    }
}
