<?php

namespace App\Jobs\Kingpin\Instance;

use App\Jobs\Job;
use App\Models\V2\Instance;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class MoveToPrivateHostGroup extends Job
{
    use Batchable, LoggableModelJob;

    private $model;
    private string $hostGroupId;

    public function __construct(Instance $instance, string $hostGroupId)
    {
        $this->model = $instance;
        $this->hostGroupId = $hostGroupId;
    }

    public function handle()
    {
        if ($this->model->hostGroup && $this->model->hostGroup->id == $this->hostGroupId) {
            Log::warning(get_class($this) . ': Instance ' . $this->model->id . ' is already in the host group ' . $this->hostGroupId . ', nothing to do');
            return;
        }

        $this->model->availabilityZone->kingpinService()
            ->post(
                '/api/v2/vpc/' . $this->model->vpc_id . '/instance/' . $this->model->id . '/reschedule',
                [
                    'json' => [
                        'hostGroupId' => $this->hostGroupId,
                    ],
                ]
            );
        Log::debug('Instance ' . $this->model->id . ' was moved to private host group ' . $this->hostGroupId);
    }
}
