<?php

namespace App\Jobs\Kingpin\Volume;

use App\Jobs\Job;
use App\Models\V2\Instance;
use App\Models\V2\Volume;
use App\Traits\V2\JobModel;
use GuzzleHttp\Exception\ServerException;
use Illuminate\Support\Facades\Log;

class Detach extends Job
{
    use JobModel;

    private Volume $model;
    private Instance $instance;

    public function __construct(Volume $volume, Instance $instance)
    {
        $this->model = $volume;
        $this->instance = $instance;
    }

    public function handle()
    {
        try {
            $response = $this->instance->availabilityZone->kingpinService()
                ->post('/api/v2/vpc/' . $this->instance->vpc_id . '/instance/' . $this->instance->id . '/volume/' . $this->model->vmware_uuid . '/detach');
        } catch (ServerException $exception) {
            $response = $exception->getResponse();
        }

        if (!$response || $response->getStatusCode() !== 200) {
            Log::error(get_class($this) . ' : Failed', [
                'id' => $this->model->id,
                'status_code' => $response->getStatusCode(),
                'content' => $response->getBody()->getContents()
            ]);
            $this->fail(new \Exception('Volume ' . $this->model->id . ' failed detachment'));
            return false;
        }

        Log::debug('Volume ' . $this->model->id . ' has been detached from instance ' . $this->instance->id);
    }

    public function failed($exception)
    {
        $this->model->setSyncFailureReason($exception->getMessage());
    }
}
