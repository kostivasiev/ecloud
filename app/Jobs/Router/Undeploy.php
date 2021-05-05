<?php

namespace App\Jobs\Router;

use App\Jobs\Job;
use App\Models\V2\Router;
use App\Traits\V2\LoggableModelJob;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class Undeploy extends Job
{
    use Batchable, LoggableModelJob;

    private $model;

    public function __construct(Router $router)
    {
        $this->model = $router;
    }

    public function handle()
    {
        try {
            $this->model->availabilityZone->nsxService()->get('policy/api/v1/infra/tier-1s/' . $this->model->id);
        } catch (ClientException $e) {
            if ($e->hasResponse() && $e->getResponse()->getStatusCode() == '404') {
                Log::info("Router already removed, skipping");
                return;
            }
            throw $e;
        }

        # Delete the router
        $this->model->availabilityZone->nsxService()->delete('policy/api/v1/infra/tier-1s/' . $this->model->id);
    }
}
