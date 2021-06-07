<?php

namespace App\Jobs\Instance;

use App\Jobs\Job;
use App\Models\V2\Instance;
use App\Traits\V2\LoggableModelJob;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class PowerOff extends Job
{
    use Batchable, LoggableModelJob;

    private $model;

    private bool $ignoreNotFound;

    const IGNORE_NOT_FOUND = true;

    public function __construct(Instance $instance, $ignoreNotFound = false)
    {
        $this->model = $instance;
        $this->ignoreNotFound = $ignoreNotFound;
    }

    public function handle()
    {
        try {
            $this->model->availabilityZone->kingpinService()->get(
                '/api/v2/vpc/' . $this->model->vpc->id . '/instance/' . $this->model->id
            );
        } catch (RequestException $exception) {
            if ($this->ignoreNotFound && $exception->getCode() == 404) {
                Log::warning(get_class($this) . ' : Attempted to power off, but instance was not found, skipping.');
                return;
            }
            throw $exception;
        }

        $this->model->availabilityZone->kingpinService()->delete(
            '/api/v2/vpc/' . $this->model->vpc->id . '/instance/' . $this->model->id . '/power'
        );
    }
}
