<?php

namespace App\Jobs\Nsx\NetworkPolicy;

use App\Jobs\Job;
use App\Models\V2\NetworkPolicy;
use Illuminate\Support\Facades\Log;

class DeployCheck extends Job
{
    const RETRY_DELAY = 5;

    public $tries = 500;

    private $model;

    public function __construct(NetworkPolicy $model)
    {
        $this->model = $model;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->model->id]);

        // NSX doesn't try to "realise" a NetworkPolicy until it has rules
        if (!count($this->model->networkRules)) {
            Log::info('No rules on the policy. Ignoring deploy check and marking policy as in sync');
            $this->model->setSyncCompleted();
            return;
        }

        $response = $this->model->network->router->availabilityZone->nsxService()->get(
            'policy/api/v1/infra/realized-state/status?intent_path=/infra/domains/default/security-policies/' . $this->model->id
        );
        $response = json_decode($response->getBody()->getContents());
        if ($response->publish_status !== 'REALIZED') {
            $this->release(static::RETRY_DELAY);
            Log::info(
                'Waiting for ' . $this->model->id . ' being deployed, retrying in ' . static::RETRY_DELAY . ' seconds'
            );
            return;
        }

        $this->model->setSyncCompleted();

        Log::info(get_class($this) . ' : Finished', ['id' => $this->model->id]);
    }
}
