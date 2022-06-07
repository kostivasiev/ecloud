<?php

namespace App\Jobs\OrchestratorBuild;

use App\Jobs\Job;
use App\Models\V2\OrchestratorBuild;
use App\Models\V2\Volume;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class CreateVolumes extends Job
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

        if (!$data->has('volumes')) {
            Log::info(
                get_class($this) . ' : OrchestratorBuild does not contain any volumes, skipping',
                ['id' => $this->model->id]
            );
            return;
        }

        collect($data->get('volumes'))->each(function ($definition, $index) use ($orchestratorBuild) {
            // Check if a resource has already been created
            if (isset($orchestratorBuild->state['volume']) && isset($orchestratorBuild->state['volume'][$index])) {
                Log::info(get_class($this) . ' : OrchestratorBuild volume. ' . $index .
                    ' has already been initiated, skipping', ['id' => $this->model->id]);
                return;
            }

            $definition = $orchestratorBuild->render($definition);

            $volume = app()->make(Volume::class);
            $volume->fill($definition->only([
                'name',
                'vpc_id',
                'availability_zone_id',
                'capacity',
                'iops',
            ])->toArray());
            $volume->syncSave();

            Log::info(get_class($this) . ' : OrchestratorBuild created volume ' . $volume->id, ['id' => $this->model->id]);

            $orchestratorBuild->updateState('volume', $index, $volume->id);
        });
    }
}
