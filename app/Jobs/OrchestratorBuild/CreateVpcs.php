<?php

namespace App\Jobs\OrchestratorBuild;

use App\Jobs\Job;
use App\Models\V2\OrchestratorBuild;
use App\Models\V2\Vpc;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;

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
        $orchestratorBuild = $this->model;

        $data = collect(json_decode($orchestratorBuild->orchestratorConfig->data));

        if (!$data->has('vpc')) {
            $this->fail(new \Exception('Orchestrator Build ' . $this->model->id . ' failed. Build data did not contain any VPC\'s'));
            return;
        }

        collect($data->get('vpc'))->each(function ($definition, $index) use ($orchestratorBuild) {
//            exit(print_r([
//                $definition,
//                $index
//            ]));

            $vpc = app()->make(Vpc::class);
            $vpc->fill(collect($definition)->only(['name', 'region_id', 'advanced_networking', 'console_enabled'])->toArray());
            $vpc->reseller_id = $orchestratorBuild->orchestratorConfig->reseller_id;
            $vpc->save();

            exit(print_r($vpc));

        });







    }
}
