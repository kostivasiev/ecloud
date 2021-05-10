<?php

namespace App\Jobs\Network;

use App\Jobs\Job;
use App\Models\V2\Network;
use App\Traits\V2\LoggableModelJob;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class AwaitPortRemoval extends Job
{
    use Batchable, LoggableModelJob;

    public $tries = 120;
    public $backoff = 5;

    private $model;

    public function __construct(Network $network)
    {
        $this->model = $network;
    }

    public function handle()
    {
        $segmentUniqueID = null;
        try {
            $response = $this->model->router->availabilityZone->nsxService()->get(
                'policy/api/v1/infra/tier-1s/' . $this->model->router->id . '/segments/' . $this->model->id
            );
            $response = json_decode($response->getBody()->getContents());
            $segmentUniqueID = $response->unique_id;
        } catch (ClientException $e) {
            if ($e->hasResponse() && $e->getResponse()->getStatusCode() == '404') {
                Log::info("Network already removed, skipping");
                return;
            }

            throw $e;
        }

        $response = $this->model->router->availabilityZone->nsxService()->get(
            '/api/v1/logical-ports?logical_switch_id=' . $segmentUniqueID
        );
        $response = json_decode($response->getBody()->getContents());
        foreach ($response->results as $result) {
            if ($result->attachment->attachment_type == 'VIF') {
                Log::info(
                    'Waiting for all ports to be removed, retrying in ' . $this->backoff . ' seconds'
                );
                return $this->release($this->backoff);
            }
        }
    }
}
