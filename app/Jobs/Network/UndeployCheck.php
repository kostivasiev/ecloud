<?php

namespace App\Jobs\Network;

use App\Jobs\Job;
use App\Models\V2\Network;
use Illuminate\Support\Facades\Log;

class UndeployCheck extends Job
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

        $response = $this->model->router->availabilityZone->nsxService()->get(
            'policy/api/v1/infra/tier-1s/' . $this->model->router->id . '/segments/?include_mark_for_delete_objects=true'
        );
        $response = json_decode($response->getBody()->getContents());
        foreach ($response->results as $result) {
            if ($this->model->id === $result->id) {
                Log::info(
                    'Waiting for ' . $this->network->id . ' being deleted, retrying in ' . $this->backoff . ' seconds'
                );
                $this->release($this->backoff);
                return;
            }
        }

        Log::info(get_class($this) . ' : Finished', ['id' => $this->model->id]);
    }
}
