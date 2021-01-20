<?php

namespace App\Jobs\Nsx\FirewallPolicy;

use App\Jobs\Job;
use App\Models\V2\FirewallPolicy;
use Illuminate\Support\Facades\Log;

class DeployCheck extends Job
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
            '/policy/api/v1/infra/realized-state/status?intent_path=/infra/domains/default/gateway-policies/' . $this->model->id
        );
        $response = json_decode($response->getBody()->getContents());

        dump($response);
        $this->release(static::RETRY_DELAY);
        return;

        foreach ($response->results as $result) {
            if ($this->model->id === $result->id) {
                dd($result);
                $this->release(static::RETRY_DELAY);
                Log::info(
                    'Waiting for ' . $this->model->id . ' being deleted, retrying in ' . static::RETRY_DELAY . ' seconds'
                );
                return;
            }
        }

        $this->model->setSyncCompleted();

        Log::info(get_class($this) . ' : Finished', ['model' => $this->model]);
    }
}
