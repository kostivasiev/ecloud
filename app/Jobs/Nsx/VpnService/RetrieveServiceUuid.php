<?php

namespace App\Jobs\Nsx\VpnService;

use App\Jobs\Job;
use App\Models\V2\VpnService;
use App\Traits\V2\LoggableModelJob;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class RetrieveServiceUuid extends Job
{
    use Batchable, LoggableModelJob;

    public $tries = 60;
    public $backoff = 5;

    public VpnService $model;

    public function __construct(VpnService $vpnService)
    {
        $this->model = $vpnService;
    }

    public function handle()
    {
        $response = $this->model->router->availabilityZone->nsxService()->get(
            '/api/v1/search/query?query=resource_type:IPSecVPNService%20AND%20display_name:' . $this->model->id
        );
        $response = json_decode($response->getBody()->getContents());
        if ($response->result_count === 0) {
            Log::info(
                'Waiting for ' . $this->model->id . ' vpn service creation, retrying in ' . $this->backoff . ' seconds'
            );
            $this->release($this->backoff);
            return;
        }
        $this->model->nsx_uuid = $response->results[0]->id;
        $this->model->saveQuietly();
    }
}
