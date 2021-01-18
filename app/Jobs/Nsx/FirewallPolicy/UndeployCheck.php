<?php

namespace App\Jobs\Nsx\FirewallPolicy;

use App\Jobs\Job;
use App\Models\V2\FirewallPolicy;
use Illuminate\Support\Facades\Log;

class UndeployCheck extends Job
{
    const RETRY_DELAY = 5;

    public $tries = 500;

    private $model;

    public function __construct(FirewallPolicy $model)
    {
        $this->model = $model;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['model' => $this->model]);

        $response = $this->model->router->availabilityZone->nsxService()->get(
            'policy/api/v1/infra/domains/default/gateway-policies/?include_mark_for_delete_objects=true'
        );
        $response = json_decode($response->getBody()->getContents());
        foreach ($response->results as $result) {
            if ($this->model->id === $result->id) {
                $this->release(static::RETRY_DELAY);
                Log::info(
                    'Waiting for ' . $this->model->id . ' being deleted, retrying in ' . static::RETRY_DELAY . ' seconds'
                );
                return;
            }
        }

        $this->model->setSyncCompleted();
        $this->model->syncDelete();

        Log::info(get_class($this) . ' : Finished', ['model' => $this->model]);
    }
}
