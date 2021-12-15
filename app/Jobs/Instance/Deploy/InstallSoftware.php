<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\Job;
use App\Models\V2\InstanceSoftware;
use App\Models\V2\Software;
use App\Models\V2\Task;
use App\Traits\V2\Jobs\AwaitResources;
use App\Traits\V2\LoggableTaskJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class InstallSoftware extends Job
{
    use Batchable, LoggableTaskJob, AwaitResources;

    private $task;

    public function __construct(Task $task)
    {
        $this->task = $task;
    }

    public function handle()
    {
        $instance = $this->task->resource;

        Log::info(
            get_class($this) . ': Install software on instance ' . $instance->id . ' started.' .
            ' Image software count = ' . $instance->image->software->count() .
            ' deploy_data = ' . json_encode($instance->deploy_data),
            [
                'id' => $instance->id
            ]
        );

        if ($instance->image->software->count() < 1 && empty($instance->deploy_data['software_ids'])) {
            Log::info(get_class($this) . ': No software to install for instance ' . $instance->id . ', skipping', ['id' => $instance->id]);
        }

        if (empty($this->task->data['instance_software_ids'])) {
            $instanceSoftwareIds = [];

            $softwareToInstall = $instance->image->software;

            if (!empty($instance->deploy_data['software_ids'])) {
                $optionalSoftware = collect($instance->deploy_data['software_ids'])
                    ->map(fn($softwareId) => Software::find($softwareId))
                    ->filter();

                $softwareToInstall = $softwareToInstall->merge($optionalSoftware);
            }
            $softwareToInstall->each(function ($software) use ($instance, &$instanceSoftwareIds) {
                Log::info(get_class($this) . ': Installing software ' . $software->name . ' (' . $software->id . ') on instance ' . $instance->id, ['id' => $instance->id]);

                $instanceSoftware = app()->make(InstanceSoftware::class);
                $instanceSoftware->name = $software->name;
                $instanceSoftware->instance()->associate($instance);
                $instanceSoftware->software()->associate($software);
                $instanceSoftware->syncSave();

                $instanceSoftwareIds[] = $instanceSoftware->id;
            });

            $this->task->setAttribute('data', ['instance_software_ids' => $instanceSoftwareIds])->saveQuietly();
        } else {
            $this->awaitSyncableResources($this->task->data['instance_software_ids']);
        }
    }
}
