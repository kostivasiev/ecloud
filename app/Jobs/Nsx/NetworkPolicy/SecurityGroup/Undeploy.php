<?php

namespace App\Jobs\Nsx\NetworkPolicy\SecurityGroup;

use App\Jobs\Job;
use App\Models\V2\NetworkPolicy;
use Illuminate\Support\Facades\Log;

class Undeploy extends Job
{
    private NetworkPolicy $networkPolicy;

    public function __construct(NetworkPolicy $networkPolicy)
    {
        $this->networkPolicy = $networkPolicy;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->networkPolicy->id]);

        $this->networkPolicy->network->router->availabilityZone->nsxService()->delete(
            'policy/api/v1/infra/domains/default/groups/' . $this->networkPolicy->id
        );

        Log::info(get_class($this) . ' : Finished', ['id' => $this->networkPolicy->id]);
    }
}
