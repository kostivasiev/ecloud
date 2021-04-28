<?php

namespace App\Jobs\Instance\Undeploy;

use App\Jobs\Job;
use App\Models\V2\Instance;
use Illuminate\Bus\Batchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class DeleteVolumes extends Job
{
    use Batchable;

    private $instance;

    public function __construct(Instance $instance)
    {
        $this->instance = $instance;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->instance->id]);

        $instance = $this->instance;

        // TODO: Check whether volumes are configured for removal on instance deletion. For now, we'll assume
        //       volume is to be deleted if connected to just this instance
        $instance->volumes()->each(function ($volume) use ($instance) {
            if ($volume->instances()->count() == 1) {
                Log::info('Detaching volume ' . $volume->id);
                Model::withoutEvents(function () use ($instance, $volume) {
                    $instance->volumes()->detach($volume->id);
                });
                Log::info('Deleting volume ' . $volume->id);
                $volume->delete();
            }
        });

        Log::info(get_class($this) . ' : Finished', ['id' => $this->instance->id]);
    }
}
