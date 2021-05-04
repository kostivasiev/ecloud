<?php

namespace App\Jobs\Router;

use App\Jobs\Job;
use App\Models\V2\Router;
use App\Traits\V2\JobModel;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class UndeployRouterLocale extends Job
{
    use Batchable, JobModel;
    
    private Router $model;

    public function __construct(Router $router)
    {
        $this->model = $router;
    }

    /**
     * @throws \Exception
     */
    public function handle()
    {
        try {
            $this->model->availabilityZone->nsxService()->get('policy/api/v1/infra/tier-1s/' . $this->model->id . '/locale-services/' . $this->model->id);
        } catch (ClientException $e) {
            if ($e->hasResponse() && $e->getResponse()->getStatusCode() == '404') {
                Log::info("Router locale already removed, skipping");
                return;
            }

            throw $e;
        }

        $this->model->availabilityZone->nsxService()
            ->delete('policy/api/v1/infra/tier-1s/' . $this->model->id . '/locale-services/' . $this->model->id);
    }
}
