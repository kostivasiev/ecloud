<?php
namespace App\Jobs\Nsx\VpnEndpoint;

use App\Jobs\Job;
use App\Models\V2\VpnEndpoint;
use App\Traits\V2\LoggableModelJob;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class Undeploy extends Job
{
    use Batchable, LoggableModelJob;

    private VpnEndpoint $model;

    public function __construct(VpnEndpoint $model)
    {
        $this->model = $model;
    }

    public function handle()
    {
        try {
            $this->model->vpnService->router->availabilityZone->nsxService()->delete(
                '/api/v1/vpn/ipsec/local-endpoints/' . $this->model->nsx_uuid
            );
        } catch (RequestException $e) {
            if ($e->hasResponse() && $e->getResponse()->getStatusCode() == '404') {
                Log::info("Vpn Endpoint doesn't exist, skipping");
                return;
            }
            throw $e;
        }
    }
}
