<?php

namespace App\Jobs\Instance\Undeploy;

use App\Jobs\Job;
use App\Models\V2\Instance;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class DeleteVolumes extends Job
{
    use Batchable, LoggableModelJob;

    private $model;

    public function __construct(Instance $instance)
    {
        $this->model = $instance;
    }

    public function handle()
    {
        $instance = $this->model;

        $instance->volumes()->each(function ($volume) use ($instance) {
            if ($volume->os_volume) {
                Log::info('Deleting OS volume ' . $volume->id);
                $volume->syncDelete();
            } else {
                Log::info('Detaching data volume ' . $volume->id);
                $instance->volumes()->detach($volume->id);
            }
        });
    }
}
