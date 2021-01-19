<?php

namespace App\Jobs\Nsx\Router;

use App\Jobs\Job;
use App\Models\V2\Router;
use Illuminate\Support\Facades\Log;

class UndeployCheck extends Job
{
    const RETRY_DELAY = 5;

    public $tries = 500;

    private $model;

    public function __construct(Router $model)
    {
        $this->model = $model;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['model' => ['id' => $this->model->id]]);

        // TODO :- Undeploy Router
        $response = $this->model->availabilityZone->nsxService()->get(
            'policy/api/v1/infra/tier-1s/' . $this->model->id . '?include_mark_for_delete_objects=true'
        );
        $response = json_decode($response->getBody()->getContents());
        foreach ($response->results as $segment) {
            if ($this->model->id === $segment->id) {
                $this->release(static::RETRY_DELAY);
                Log::info(
                    'Waiting for segment ' . $this->model->id . ' being deleted, retrying in ' . static::RETRY_DELAY . ' seconds'
                );
                return;
            }
        }

        $this->model->setSyncCompleted();
        $this->model->syncDelete();

        Log::info(get_class($this) . ' : Finished', ['model' => ['id' => $this->model->id]]);
    }
}
