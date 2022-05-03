<?php

namespace App\Jobs\Nic;

use App\Jobs\TaskJob;
use GuzzleHttp\Exception\RequestException;

class Undeploy extends TaskJob
{
    public function handle()
    {
        $nic = $this->task->resource;
        $instance = $nic->instance;

        try {
            $instance->availabilityZone->kingpinService()->delete(
                '/api/v2/vpc/' . $instance->vpc->id .
                '/instance/' . $instance->id .
                '/nic/' . $nic->mac_address
            );
            $this->info('NIC ' . $nic->id . ' was removed from instance ' . $instance->id);
        } catch (RequestException $exception) {
            if ($exception->getCode() != 404) {
                throw $exception;
            }
            $this->info('NIC was not found on the instance, nothing to do, skipping.');
        }
    }
}
