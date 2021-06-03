<?php

namespace App\Jobs\Kingpin\Instance;

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
    private $hostGroupId;

    public function __construct(Instance $instance, string $hostGroupId)
    {
        $this->model = $instance;
        $this->hostGroupId = $hostGroupId;
    }

    public function handle()
    {
        try {
            $this->model->availabilityZone->kingpinService()
                ->post(
                    '/api/v2/vpc/' . $this->model->vpc_id . '/instance/' . $this->model->id . '/reschedule',
                    [
                        'json' => [
                            'hostGroupId' => $this->hostGroupId,
                        ],
                    ]
                );
        } catch (RequestException $exception) {
            $this->fail($exception);
            return false;
        }
        Log::debug('Hostgroup ' . $this->hostGroupId . ' has been attached to instance ' . $this->model->id);
    }
}
