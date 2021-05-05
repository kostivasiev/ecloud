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

        // TODO: Check whether volumes are configured for removal on instance deletion. For now, we'll assume
        //       volume is to be deleted if connected to just this instance
        $instance->volumes()->each(function ($volume) use ($instance) {
            if ($volume->instances()->count() == 1) {
                Log::info('Detaching volume ' . $volume->id);
                $instance->volumes()->detach($volume->id);

                Log::info('Deleting volume ' . $volume->id);
                $volume->delete();
            }
        });
    }
}
