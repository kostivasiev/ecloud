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

    const RETRY_MAX = 60;
    const RETRY_DELAY = 5;

    private Dhcp $model;

    public function __construct(Dhcp $dhcp)
    {
        $this->model = $dhcp;
    }

    public function handle()
    {
        if ($this->attempts() > static::RETRY_MAX) {
            throw new \Exception('Failed waiting for ' . $this->model->id . ' to be deleted after ' . static::RETRY_MAX . ' attempts');
        }

        $response = $this->model->availabilityZone->nsxService()->get(
            '/policy/api/v1/infra/dhcp-server-configs/?include_mark_for_delete_objects=true'
        );
        $response = json_decode($response->getBody()->getContents());
        foreach ($response->results as $result) {
            if ($this->model->id === $result->id) {
                Log::info(
                    'Waiting for ' . $this->model->id . ' to be deleted, retrying in ' . static::RETRY_DELAY . ' seconds'
                );

                return $this->release(static::RETRY_DELAY);
            }
        }
    }
}
