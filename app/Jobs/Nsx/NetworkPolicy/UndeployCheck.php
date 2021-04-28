<?php

namespace App\Jobs\Nsx\NetworkPolicy;

use App\Jobs\Job;
use App\Models\V2\NetworkPolicy;
use Illuminate\Support\Facades\Log;

class UndeployCheck extends Job
{
    const RETRY_DELAY = 5;

    public $tries = 500;

    private $networkPolicy;

    public function __construct(NetworkPolicy $networkPolicy)
    {
        $this->networkPolicy = $networkPolicy;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->networkPolicy->id]);

        $response = $this->networkPolicy->network->router->availabilityZone->nsxService()->get(
            'policy/api/v1/infra/domains/default/security-policies/?include_mark_for_delete_objects=true'
        );
        $response = json_decode($response->getBody()->getContents());
        foreach ($response->results as $result) {
            if ($this->networkPolicy->id === $result->id) {
                $this->release(static::RETRY_DELAY);
                Log::info(
                    'Waiting for ' . $this->networkPolicy->id . ' being deleted, retrying in ' . static::RETRY_DELAY . ' seconds'
                );
                return;
            }
        }

        Log::info(get_class($this) . ' : Finished', ['id' => $this->networkPolicy->id]);
    }
}
