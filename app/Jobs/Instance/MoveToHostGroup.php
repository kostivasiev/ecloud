<?php

namespace App\Jobs\Instance;

use App\Jobs\Job;
use App\Models\V2\Instance;
use App\Traits\V2\LoggableModelJob;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class MoveToHostGroup extends Job
{
    use Batchable, LoggableModelJob;

    private $model;

    public function __construct(Instance $instance)
    {
        $this->model = $instance;
    }

    public function handle()
    {
        try {
            $this->model->availabilityZone->kingpinService()->post(
                '/api/v2/vpc/' . $this->model->vpc_id . '/instance/' . $this->model->id . '/reschedule',
                [
                    'json' => [
                        'hostGroupId' => $this->model->host_group_id,
                    ],
                ]
            );
        } catch (RequestException $exception) {
            $message = 'Unable to move ' . $this->model->id . ' to host group ' . $this->model->host_group_id;
            Log::warning(get_class($this) . ' : ' . $message);
        }
    }
}
