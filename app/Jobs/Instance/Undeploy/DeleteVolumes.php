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

        $instance->volumes()->each(function ($volume) use ($instance) {
            if ($volume->os_volume) {
                Log::info('Deleting OS volume ' . $volume->id);
                $volume->delete();
            } else {
                Log::info('Detaching data volume ' . $volume->id);
                $instance->volumes()->detach($volume->id);
            }
        });

        Log::info(get_class($this) . ' : Finished', ['id' => $this->instance->id]);
    }
}
