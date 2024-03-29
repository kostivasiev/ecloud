<?php

namespace App\Jobs\OrchestratorBuild;

use App\Jobs\Job;
use App\Models\V2\HostGroup;
use App\Models\V2\OrchestratorBuild;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class CreateHostGroups extends Job
{
    use Batchable, LoggableModelJob;

    private OrchestratorBuild $model;
    private array $hosts;

    public function __construct(OrchestratorBuild $orchestratorBuild)
    {
        $this->model = $orchestratorBuild;
    }

    public function handle()
    {
        $orchestratorBuild = $this->model;
        $data = collect(json_decode($orchestratorBuild->orchestratorConfig->data));

        if (!$data->has('hostgroups')) {
            Log::info(get_class($this) . ' : OrchestratorBuild does not contain any Hostgroups, skipping', ['id' => $this->model->id]);
            return;
        }

        collect($data->get('hostgroups'))->each(function ($definition, $index) use ($orchestratorBuild) {
            // Check if a resource has already been created
            if (isset($orchestratorBuild->state['hostgroup']) && isset($orchestratorBuild->state['hostgroup'][$index])) {
                Log::info(get_class($this) . ' : OrchestratorBuild hostgroup ' . $index . ' has already been initiated, skipping', ['id' => $this->model->id]);
                return;
            }

            $definition = $orchestratorBuild->render($definition);

            $hostgroup = app()->make(HostGroup::class);
            $hostgroup->fill($definition->only(['name', 'vpc_id', 'availability_zone_id', 'host_spec_id', 'windows_enabled'])->toArray());
            $hostgroup->syncSave();

            Log::info(get_class($this) . ' : OrchestratorBuild created Hostgroup ' . $hostgroup->id, ['id' => $this->model->id]);

            $orchestratorBuild->updateState('hostgroup', $index, $hostgroup->id);
        });
    }
}
