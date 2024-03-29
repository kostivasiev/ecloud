<?php

namespace App\Jobs\Nsx\NetworkPolicy;

use App\Jobs\Job;
use App\Models\V2\NetworkPolicy;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class UndeployCheck extends Job
{
    use Batchable, LoggableModelJob;

    const RETRY_DELAY = 5;

    public $tries = 500;

    private $model;

    public function __construct(NetworkPolicy $networkPolicy)
    {
        $this->model = $networkPolicy;
    }

    public function handle()
    {
        $response = $this->model->network->router->availabilityZone->nsxService()->get(
            'policy/api/v1/infra/domains/default/security-policies/?include_mark_for_delete_objects=true'
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
    }
}
