<?php

namespace App\Jobs\Kingpin\Host;

use App\Jobs\Job;
use App\Models\V2\Host;
use App\Traits\V2\JobModel;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class DeleteInVmware extends Job
{
    use Batchable, JobModel;

    public $tries = 60;
    public $backoff = 60;

    private Host $model;

    public function __construct(Host $host)
    {
        $this->model = $host;
    }

    public function handle()
    {
        $availabilityZone = $this->model->hostGroup->availabilityZone;
        $hostGroup = $this->model->hostGroup;

        // Get the host spec from Conjurer
        try {
            $response = $availabilityZone->conjurerService()->get(
                '/api/v2/compute/' . $availabilityZone->ucs_compute_name . '/vpc/' . $hostGroup->vpc->id . '/host/' . $this->model->id
            );
            $response = json_decode($response->getBody()->getContents());
        } catch (RequestException $exception) {
            if ($exception->getCode() != 404) {
                $this->fail($exception);
            }
            Log::warning(get_class($this) . ' : Host was not found on UCS, skipping.');
            return false;
        }

        $macAddress = collect($response->interfaces)->firstWhere('name', '=', 'eth0')->address;
        if (empty($macAddress)) {
            $message = 'Failed to load eth0 address for host ' . $this->model->id;
            Log::error($message);
            $this->fail(new \Exception($message));
            return false;
        }

        Log::debug('MAC address: ' . $macAddress);

        try {
            $availabilityZone->kingpinService()->delete(
                '/api/v2/vpc/' . $hostGroup->vpc_id . '/hostgroup/' . $hostGroup->id . '/host/' . $macAddress
            );
        } catch (RequestException $exception) {
            if ($exception->getCode() != 404) {
                throw $exception;
            }
            Log::warning(get_class($this) . ' : Host could not be deleted, skipping.');
            return false;
        }
    }
}
