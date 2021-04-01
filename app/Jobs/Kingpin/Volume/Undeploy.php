<?php

namespace App\Jobs\Kingpin\Volume;

use App\Jobs\Job;
use App\Models\V2\Volume;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
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
            // TODO :- Move this to a deleation rule, it's not right doing it here?
            throw new \Exception('Volume ' . $this->model->id . ' had instances when trying to delete');
        }

        try {
            $response = $this->model->availabilityZone->kingpinService()->delete(
                '/api/v1/vpc/' . $this->model->vpc->id . '/volume/' . $this->model->vmware_uuid
            );
        } catch (ClientException|ServerException $exception) {
            $response = $exception->getResponse();
        }

        if (!$response || $response->getStatusCode() !== 200) {
            Log::error(get_class($this) . ' : Failed', [
                'id' => $this->model->id,
                'status_code' => $response->getStatusCode(),
                'content' => $response->getBody()->getContents()
            ]);
            if ($response->getStatusCode() !== 404) {
                $this->fail(new \Exception('Volume ' . $this->model->id . ' failed to be deleted'));
                return false;
            }
        }

        Log::info(get_class($this) . ' : Finished', ['id' => $this->model->id]);
    }
}
