<?php

namespace App\Jobs\Kingpin\Volume;

use App\Jobs\Job;
use App\Models\V2\Volume;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class Undeploy extends Job
{
    use Batchable;

    public $model;

    public function __construct(Volume $model)
    {
        $this->model = $model;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->model->id]);

        if (empty($this->model->vmware_uuid)) {
            Log::warning(get_class($this) . ' : No VMware UUID disk set for volume, skipping', ['id' => $this->model->id]);
            return;
        }

        if ($this->model->instances()->count() !== 0) {
            throw new \Exception('Volume ' . $this->model->id . ' had instances when trying to delete');
        }

        try {
            $this->model->availabilityZone->kingpinService()->delete(
                '/api/v2/vpc/' . $this->model->vpc->id . '/volume/' . $this->model->vmware_uuid
            );
        } catch (RequestException $exception) {
            if ($exception->getCode() != 404) {
                throw $exception;
            }
            Log::debug(get_class($this) . ' : Volume was not found, nothing to do.');
        }

        Log::info(get_class($this) . ' : Finished', ['id' => $this->model->id]);
    }
}
