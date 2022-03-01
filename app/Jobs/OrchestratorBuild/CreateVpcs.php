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
        $orchestratorBuild = $this->model;

        $data = collect(json_decode($orchestratorBuild->orchestratorConfig->data));

        if (!$data->has('vpcs')) {
            Log::info(get_class($this) . ' : OrchestratorBuild does not contain any VPC\'s, skipping', ['id' => $this->model->id]);
            return;
        }

        collect($data->get('vpcs'))->each(function ($definition, $index) use ($orchestratorBuild) {
            // Check if a resource has already been created
            if (isset($orchestratorBuild->state['vpc']) && isset($orchestratorBuild->state['vpc'][$index])) {
                Log::info(get_class($this) . ' : OrchestratorBuild vpc. ' . $index . ' has already been initiated, skipping', ['id' => $this->model->id]);
                return;
            }

            $definition = collect($definition);

            $vpc = app()->make(Vpc::class);
            $vpc->fill($definition->only(['name', 'region_id', 'advanced_networking', 'console_enabled'])->toArray());
            $vpc->reseller_id = $orchestratorBuild->orchestratorConfig->reseller_id;
            $vpc->syncSave();


            Log::info(get_class($this) . ' : OrchestratorBuild created VPC ' . $vpc->id, ['id' => $this->model->id]);

            $orchestratorBuild->updateState('vpc', $index, $vpc->id);
        });
    }
}
