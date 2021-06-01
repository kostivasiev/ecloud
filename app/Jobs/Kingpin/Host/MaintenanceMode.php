<?php

namespace App\Jobs\Kingpin\Host;

use App\Jobs\Job;
use App\Models\V2\Host;
use App\Traits\V2\LoggableModelJob;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class MaintenanceMode extends Job
{
    use Batchable, LoggableModelJob;

    private $model;

    public function __construct(Host $model)
    {
        $this->model = $model;
    }

    public function handle()
    {
        $host = $this->model;
        $hostGroup = $this->model->hostGroup;
        $availabilityZone = $hostGroup->availabilityZone;

        // Get the host spec from Conjurer
        try {
            $response = $availabilityZone->conjurerService()->get(
                '/api/v2/compute/' . $availabilityZone->ucs_compute_name . '/vpc/' . $hostGroup->vpc->id . '/host/' . $host->id
            );
        } catch (RequestException $exception) {
            if ($exception->getCode() != 404) {
                $this->fail($exception);
            }
            Log::warning(get_class($this) . ' : Host Spec for ' . $host->id . ' could not be retrieved, skipping.');
            return false;
        }
        $responseJson = json_decode($response->getBody()->getContents());

        $macAddress = collect($responseJson->interfaces)->firstWhere('name', 'eth0')->address;
        if (empty($macAddress)) {
            $message = 'Failed to load eth0 address for host ' . $host->id;
            Log::error($message);
            $this->fail(new \Exception($message));
            return false;
        }

        // Put host into maintenance mode
        Log::info('Mac Address: ' . $macAddress);
        try {
            $response = $availabilityZone->kingpinService()
                ->post(
                    '/api/v2/vpc/' . $hostGroup->vpc->id . '/hostgroup/' . $hostGroup->id . '/host/' . $macAddress . '/maintenance'
                );
        } catch (RequestException $exception) {// handle 40x/50x response if host not found
            $message = 'Error while putting Host ' . $host->id . ' into maintenance mode.';
            if ($exception->getCode() != 404) {
                $this->fail(new \Exception($message));
            }
            Log::error($message, [
                'vpc_id' => $hostGroup->vpc->id,
                'hostgroup' => $hostGroup->id,
                'macAddress' => $macAddress
            ]);
            return false;
        }
        if (!$response || $response->getStatusCode() !== 200) {
            Log::error(get_class($this) . ' : Failed', [
                'id' => $host->id,
                'status_code' => $response->getStatusCode(),
                'content' => $response->getBody()->getContents()
            ]);
            $this->fail(new \Exception('Host ' . $host->id . ' could not be put into maintenance mode.'));
            return false;
        }
    }
}
