<?php

namespace App\Jobs\Kingpin\Instance;

use App\Jobs\Instance\Undeploy\DeleteNics;
use App\Jobs\Instance\Undeploy\DeleteVolumes;
use App\Jobs\Instance\Undeploy\PowerOff;
use App\Jobs\Instance\Undeploy\Undeploy as InstanceUndeploy;
use App\Jobs\Instance\Undeploy\UndeployCompleted;
use App\Jobs\Job;
use App\Models\V2\Instance;
use Illuminate\Support\Facades\Log;

class Undeploy extends Job
{
    private $model;

    public function __construct(Instance $model)
    {
        $this->model = $model;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->model->id]);

        $data = [
            'instance_id' => $this->model->id,
            'vpc_id' => $this->model->vpc->id,
        ];

        $jobs = [
            new PowerOff($data, false),
            new InstanceUndeploy($data),
            new DeleteVolumes($data),
            new DeleteNics($data),
            new UndeployCompleted($data),
        ];

        dispatch(array_shift($jobs)->chain($jobs));

        Log::info(get_class($this) . ' : Finished', ['id' => $this->model->id]);
    }
}