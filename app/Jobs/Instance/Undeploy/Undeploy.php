<?php

namespace App\Jobs\Instance\Undeploy;

use App\Jobs\Job;
use App\Models\V2\Instance;
use App\Traits\V2\LoggableModelJob;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class Undeploy extends Job
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
            $this->model->availabilityZone->kingpinService()->get(
                '/api/v2/vpc/' . $this->model->vpc->id . '/instance/' . $this->model->id
            );
        } catch (RequestException $exception) {
            if ($exception->getCode() != 404) {
                throw $exception;
            }
            Log::warning(get_class($this) . ' : Attempted to Undeploy instance, but instance was not was not found, skipping.');
            return;
        }

        $this->model->availabilityZone
            ->kingpinService()
            ->delete('/api/v2/vpc/' . $this->model->vpc->id . '/instance/' . $this->model->id);
    }
}
