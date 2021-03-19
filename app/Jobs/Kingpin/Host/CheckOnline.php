<?php

namespace App\Jobs\Kingpin\Host;

use App\Jobs\Job;
use App\Models\V2\Host;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use Illuminate\Support\Facades\Log;

class CheckOnline extends Job
{
    const RETRY_DELAY = 5;
    public $tries = 60;
    private $model;

    public function __construct(Host $model)
    {
        $this->model = $model;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->model->id]);

        $host = $this->model;
        $availabilityZone = $host->hostGroup->availabilityZone;

        // Get the host spec from Conjurer
        $response = $availabilityZone->conjurerService()->get(
            '/api/v2/compute/' . $availabilityZone->ucs_compute_name . '/vpc/' . $host->hostGroup->vpc->id .'/host/' . $host->id
        );
        $response = json_decode($response->getBody()->getContents());

        $macAddress = collect($response->interfaces)->firstWhere('name', 'eth0')->address;
        if (empty($macAddress)) {
            $message = 'Failed to load eth0 address for host ' . $host->id;
            Log::error($message);
            $this->fail(new \Exception($message));
            return false;
        }

        try {
            $response = $host->hostGroup->availabilityZone->kingpinService()->get(
                '/api/v2/vpc/' . $host->hostGroup->vpc_id . '/hostgroup/' . $host->hostGroup->id . '/host/' . $macAddress
            );
            $response = json_decode($response->getBody()->getContents());
        } catch (ClientException|ServerException $e) { // handle 40x/50x response if host not found
            Log::debug('Waiting for Host ' . $host->id . ' to come online...');
            $this->release(static::RETRY_DELAY);
            return false;
        }

        if ($response->powerState !== 'On') {
            Log::debug('Waiting for Host ' . $host->id . ' power state...');
            $this->release(static::RETRY_DELAY);
            return false;
        }

        // Power on received - move to next step

        Log::info(get_class($this) . ' : Finished', ['id' => $this->model->id]);
    }

    public function failed($exception)
    {
        $message = ($exception instanceof RequestException && $exception->hasResponse()) ?
            $exception->getResponse()->getBody()->getContents() :
            $exception->getMessage();
        $this->model->setSyncFailureReason($message);
    }
}
