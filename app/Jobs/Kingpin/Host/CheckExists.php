<?php

namespace App\Jobs\Kingpin\Host;

use App\Jobs\Job;
use App\Models\V2\Host;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use Illuminate\Support\Facades\Log;

class CheckExists extends Job
{
    private $model;

    public function __construct(Host $model)
    {
        $this->model = $model;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->model->id]);

        $host = $this->model;
        $hostGroup = $this->model->hostGroup;
        $availabilityZone = $hostGroup->availabilityZone;

        // Get the host spec from Conjurer
        $response = $availabilityZone->conjurerService()->get(
            '/api/v2/compute/' . $availabilityZone->ucs_compute_name . '/vpc/' . $hostGroup->vpc->id .'/host/' . $host->id
        );
        $responseJson = json_decode($response->getBody()->getContents());
        if (!$response || $response->getStatusCode() !== 200) {
            Log::error(get_class($this) . ' : Failed', [
                'id' => $host->id,
                'status_code' => $response->getStatusCode(),
                'content' => $response->getBody()->getContents()
            ]);
            $this->fail(new \Exception('Host Spec for ' . $host->id . ' could not be retrieved.'));
            return false;
        }

        $macAddress = collect($responseJson->interfaces)->firstWhere('name', 'eth0')->address;
        if (empty($macAddress)) {
            $message = 'Failed to load eth0 address for host ' . $host->id;
            Log::error($message);
            $this->fail(new \Exception($message));
            return false;
        }

        // Check host exists
        try {
            $response = $availabilityZone->kingpinService()
                ->get(
                    '/api/v2/vpc/' . $hostGroup->vpc->id . '/hostgroup/' . $hostGroup->id . '/host/' . $macAddress
                );
        } catch (ClientException|ServerException $e) {// handle 40x/50x response if host not found
            $message = 'Error while checking if Host ' . $host->id . ' exists.';
            Log::debug($message);
            $this->fail(new \Exception($message));
            return false;
        }

        if (!$response || $response->getStatusCode() !== 200) {
            Log::error(get_class($this) . ' : Failed', [
                'id' => $host->id,
                'status_code' => $response->getStatusCode(),
                'content' => $response->getBody()->getContents()
            ]);
            $this->fail(new \Exception('Host ' . $host->id . ' could not be found.'));
            return false;
        }

        Log::info(get_class($this) . ' : Finished', ['id' => $this->model->id]);
    }

    public function failed($exception)
    {
        $this->model->setSyncFailureReason($exception->getMessage());
    }
}
