<?php

namespace App\Jobs\Nsx\Network;

use App\Jobs\Job;
use App\Models\V2\Network;
use Illuminate\Support\Facades\Log;

class Undeploy extends Job
{
    const RETRY_DELAY = 5;

    public $tries = 500;

    private $model;

    public function __construct(Network $model)
    {
        $this->model = $model;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->model->id]);

        $response = $this->model->router->availabilityZone->nsxService()->delete(
            'policy/api/v1/infra/tier-1s/' . $this->model->router->id . '/segments/' . $this->model->id
        );
        if (!$response || $response->getStatusCode() !== 200) {
            Log::error(get_class($this) . ' : Failed', [
                'id' => $this->model->id,
                'status_code' => $response->getStatusCode(),
                'content' => $response->getBody()->getContents()
            ]);
            $this->model->setSyncFailureReason('Failed to delete "' . $this->model->id . '"');
            $this->fail(new \Exception('Failed to delete "' . $this->model->id . '"'));
            return;
        }

        Log::info(get_class($this) . ' : Finished', ['id' => $this->model->id]);
    }
}
