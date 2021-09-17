<?php

namespace App\Jobs\Nsx\Dhcp;

use App\Jobs\Job;
use App\Models\V2\Dhcp;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class UndeployCheck extends Job
{
    use Batchable, LoggableModelJob;

    private Dhcp $model;

    public $tries = 60;
    public $backoff = 5;

    public function __construct(Dhcp $dhcp)
    {
        $this->model = $dhcp;
    }

    public function handle()
    {
        $response = $this->model->availabilityZone->nsxService()->get(
            '/policy/api/v1/infra/dhcp-server-configs/?include_mark_for_delete_objects=true'
        );
        $response = json_decode($response->getBody()->getContents());
        foreach ($response->results as $result) {
            if ($this->model->id === $result->id) {
                Log::info(
                    'Waiting for ' . $this->model->id . ' being deleted, retrying in ' . $this->backoff . ' seconds'
                );
                $this->release($this->backoff);
                return;
            }
        }
    }
}
