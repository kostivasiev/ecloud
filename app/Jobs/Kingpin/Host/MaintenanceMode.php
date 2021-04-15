<?php

namespace App\Jobs\Kingpin\Host;

use App\Jobs\Job;
use App\Models\V2\Host;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class MaintenanceMode extends Job
{
    use Batchable;

    private $model;

    public function __construct(Host $model)
    {
        $this->model = $model;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->model->id]);
        if (!$this->batch()->cancelled()) {
            $host = $this->model;
            $hostGroup = $this->model->hostGroup;
            $availabilityZone = $hostGroup->availabilityZone;

            $macAddress = $this->getMacAddress($host, $hostGroup, $availabilityZone);

            // Put host into maintenance mode
            if (!empty($macAddress)) {
                Log::info('Mac Address: ' . $macAddress);
                try {
                    $response = $availabilityZone->kingpinService()
                        ->post(
                            '/api/v2/vpc/' . $hostGroup->vpc->id . '/hostgroup/' . $hostGroup->id . '/host/' . $macAddress . '/maintenance'
                        );
                } catch (RequestException $e) {// handle 40x/50x response if host not found
                    $message = 'Error while putting Host ' . $host->id . ' into maintenance mode.';
                    Log::error($message, [
                        'vpc_id' => $hostGroup->vpc->id,
                        'hostgroup' => $hostGroup->id,
                        'macAddress' => $macAddress
                    ]);
                    $this->fail(new \Exception($message));
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
        Log::info(get_class($this) . ' : Finished', ['id' => $this->model->id]);
    }

    public function getMacAddress($host, $hostGroup, $availabilityZone)
    {
        // Get the host spec from Conjurer
        try {
            $response = $availabilityZone->conjurerService()->get(
                '/api/v2/compute/' . $availabilityZone->ucs_compute_name . '/vpc/' . $hostGroup->vpc->id . '/host/' . $host->id
            );
        } catch (RequestException $exception) {
            if ($exception->getCode() != 404) {
                throw $exception;
            }
            Log::warning(get_class($this) . ' : Host Spec for ' . $host->id . ' could not be retrieved.');
            return false;
        }
        $responseJson = json_decode($response->getBody()->getContents());

        $macAddress = collect($responseJson->interfaces)->firstWhere('name', 'eth0')->address;
        if (empty($macAddress)) {
            $message = 'Failed to load eth0 address for host ' . $host->id;
            Log::error($message);
            return false;
        }
        return $macAddress;
    }
}
