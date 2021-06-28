<?php

namespace App\Jobs\OrchestratorBuild;

use App\Jobs\Job;
use App\Models\V2\OrchestratorBuild;
use App\Models\V2\Vpc;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class CreateVpcs extends Job
{
    use Batchable, LoggableModelJob;

    private OrchestratorBuild $model;

    public function __construct(OrchestratorBuild $orchestratorBuild)
    {
        $this->model = $orchestratorBuild;
    }

    public function handle()
    {
        $data = json_decode($this->model->orchestratorConfig->data, true);

        if (!isset($data['vpc'])) {
            $this->fail(new \Exception('Orchestrator Build ' . $this->model->id . ' failed. Build data did not contain any VPC\'s'));
            return;
        }



//        $vpc = app()->make(Vpc::class);
//
//        $vpc->save();


    }
}
