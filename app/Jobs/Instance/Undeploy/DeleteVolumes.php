<?php

namespace App\Jobs\Instance\Undeploy;

use App\Jobs\Job;
use App\Models\V2\Instance;
use Illuminate\Bus\Batchable;
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

        $instance->volumes()->each(function ($volume) use ($instance) {
            // $instance->volumes()->detach($volume); // No need to detach from deleted instance?!
            if ($volume->instances()->count() == 0) {
                Log::info('Deleting volume ' . $volume->id);
                $volume->delete();
            }
        });

        Log::info(get_class($this) . ' : Finished', ['id' => $this->instance->id]);
    }
}
