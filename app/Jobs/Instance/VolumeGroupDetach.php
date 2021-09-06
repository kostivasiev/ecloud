<?php
namespace App\Jobs\Instance;

use App\Jobs\Job;
use App\Jobs\Tasks\Instance\VolumeDetach;
use App\Models\V2\Instance;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class VolumeGroupDetach extends Job
{
    use Batchable, LoggableModelJob;

    public $tries = 60;
    public $backoff = 5;

    private Instance $model;

    public function __construct(Instance $instance)
    {
        $this->model = $instance;
    }

    public function handle()
    {
        $instance = $this->model;
        if (!empty($instance->volume_group_id)) {
            Log::info(
                'No volumes to unmount from Instance, skipping',
                [
                    'instance_id' => $instance->id,
                ]
            );
            return;
        }
        $instance->volumes()->where('is_shared', '=', true)
            ->each(function ($volume) use ($instance) {
                $instance->createTask('volume_detach', VolumeDetach::class, ['volume_id' => $volume->id]);
                Log::info(
                    'Detaching volume from instance, retrying in ' . $this->backoff . ' seconds',
                    [
                        'instance_id' => $instance->id,
                        'volume_id' => $volume->id,
                    ]
                );
                return $this->release($this->backoff);
            });
    }
}
