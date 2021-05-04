<?php

namespace App\Jobs\Network;

use App\Jobs\Job;
use App\Models\V2\Network;
use App\Traits\V2\JobModel;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;
use IPLib\Range\Subnet;

class Undeploy extends Job
{
    use Batchable, JobModel;

    private Network $model;

    public function __construct(Network $network)
    {
        $this->model = $network;
    }

    public function handle()
    {
        try {
            $this->model->router->availabilityZone->nsxService()->get(
                'policy/api/v1/infra/tier-1s/' . $this->model->router->id . '/segments/' . $this->model->id
            );
        } catch (ClientException $e) {
            if ($e->hasResponse() && $e->getResponse()->getStatusCode() == '404') {
                Log::info("Router already removed, skipping");
                return;
            }

            throw $e;
        }
        $this->model->router->availabilityZone->nsxService()->delete(
            'policy/api/v1/infra/tier-1s/' . $this->model->router->id . '/segments/' . $this->model->id
        );
    }
}
