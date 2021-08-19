<?php

namespace App\Jobs\Nsx\VpnEndpoint;

use App\Jobs\Job;
use App\Models\V2\Task;
use App\Models\V2\VpnEndpoint;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

/**
 * @deprecated
 */
class RetrieveEndpointUuid extends Job
{
    use Batchable, LoggableModelJob;

    public $tries = 60;
    public $backoff = 5;

    public VpnEndpoint $model;

    public function __construct(Task $task)
    {
        $this->model = $task->resource;
    }

    public function handle()
    {
        $response = $this->model->vpnService->router->availabilityZone->nsxService()->get(
            '/api/v1/search/query?query=resource_type:IPSecVPNLocalEndpoint%20AND%20display_name:' . $this->model->id
        );
        $response = json_decode($response->getBody()->getContents());
        if ($response->result_count === 0) {
            Log::info(
                'Waiting for ' . $this->model->id . ' vpn endpoint creation, retrying in ' . $this->backoff . ' seconds'
            );
            $this->release($this->backoff);
            return;
        }
        $this->model->nsx_uuid = $response->results[0]->id;
        $this->model->saveQuietly();
    }
}
