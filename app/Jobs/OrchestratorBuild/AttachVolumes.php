<?php

namespace App\Jobs\OrchestratorBuild;

use App\Jobs\Job;
use App\Jobs\Tasks\Instance\VolumeAttach;
use App\Models\V2\Instance;
use App\Models\V2\OrchestratorBuild;
use App\Models\V2\Volume;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class AttachVolumes extends Job
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

        if (!$data->has('volume_attaches')) {
            Log::info(
                get_class($this) . ' : OrchestratorBuild does not contain any volumes to mount, skipping',
                ['id' => $this->model->id]
            );
            return;
        }

        collect($data->get('volume_attaches'))->each(function ($definition, $index) use ($orchestratorBuild) {
            // Check if a resource has already been created
            if (isset($orchestratorBuild->state['volume_attach']) && isset($orchestratorBuild->state['volume_attach'][$index])) {
                Log::info(get_class($this) . ' : OrchestratorBuild mounting of volume. ' . $index .
                    ' has already been initiated, skipping', ['id' => $this->model->id]);
                return;
            }

            $definition = json_decode($orchestratorBuild->render($definition));

            // here we need to mount the volume using $definition->id for the volumeId and $definition->instance_id for the instance
            $instance = Instance::findOrFail($definition->instance_id);
            $volume = Volume::findOrFail($definition->volume_id);
            $instance->createTaskWithLock('volume_attach', VolumeAttach::class, ['volume_id' => $volume->id]);

            Log::info(get_class($this) . ' : OrchestratorBuild mounting volume ' . $volume->id, [
                'id' => $volume->id,
                'instance_id' => $instance->id,
            ]);

            $orchestratorBuild->updateState('volume_attach', $index, $instance->id);
        });
    }
}
