<?php
namespace App\Jobs\Instance;

use App\Jobs\Job;
use App\Jobs\Tasks\Instance\VolumeAttach;
use App\Models\V2\Instance;
use App\Traits\V2\Jobs\AwaitTask;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class VolumeGroupAttach extends Job
{
    use Batchable, LoggableModelJob, AwaitTask;

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
            $instance->volumeGroup->volumes()->each(function ($volume) use ($instance) {
                if ($instance->volumes()->where('id', '=', $volume->id)->count() > 0) {
                    Log::info(
                        'Volume is already mounted on Instance, skipping',
                        [
                            'instance_id' => $instance->id,
                            'volume_id' => $volume->id,
                        ]
                    );
                    return;
                }

                Log::info(
                    'Attaching volume to instance',
                    [
                        'instance_id' => $instance->id,
                        'volume_id' => $volume->id,
                    ]
                );

                try {
                    $task = $instance->createTask('volume_attach', VolumeAttach::class, ['volume_id' => $volume->id], true);
                } catch (\Exception $exception) {
                    $this->fail(new \Exception($exception->getMessage()));
                    return false;
                }

                $this->awaitTask($task);
            });
        }
    }
}
