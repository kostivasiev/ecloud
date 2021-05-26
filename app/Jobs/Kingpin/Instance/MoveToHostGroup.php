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
            $response = $this->model->availabilityZone->kingpinService()->get(
                '/api/v2/vpc/' . $this->model->vpc->id . '/instance/' . $this->model->id
            );
        } catch (RequestException $exception) {
            $message = 'Failed to retrieve instance ' . $this->model->id . ' from Kingpin';
            Log::warning(get_class($this) . ' : ' . $message);
            throw new \Exception($message);
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
        Log::debug('Hostgroup ' . $this->hostGroupId . ' has been attached to instance ' . $this->model->id);
    }
}
