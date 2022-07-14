<?php

namespace App\Jobs\Conjurer\Host;

use App\Jobs\Job;
use App\Models\V2\Host;
use App\Traits\V2\LoggableModelJob;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CreateProfile extends Job
{
    use Batchable, LoggableModelJob;

    private Host $model;

    public function __construct(Host $host)
    {
        $this->model = $host;
    }

    public function handle()
    {
        $hostGroup = $this->model->hostGroup;
        $availabilityZone = $hostGroup->availabilityZone;

        // Check whether profile exists
        try {
            $availabilityZone->conjurerService()->get(
                '/api/v2/compute/' . $availabilityZone->ucs_compute_name . '/vpc/' . $hostGroup->vpc->id . '/host/' . $this->model->id
            );

            Log::debug('Profile already exists, skipping');
            return true;
        } catch (RequestException $exception) {
            if ($exception->hasResponse() && $exception->getResponse()->getStatusCode() != 404) {
                throw $exception;
            }
        }

        $lock = Cache::lock('hostspec_available.' . $this->model->hostGroup->hostSpec->id, 60);
        try {
            $lock->block(60);

            $response = $availabilityZone->conjurerService()->get(
                '/api/v2/compute/' . $availabilityZone->ucs_compute_name . '/specification/' . $this->model->hostGroup->hostSpec->ucs_specification_name . '/host/available'
            );
            $response = json_decode($response->getBody()->getContents());

            if (!is_array($response)) {
                $this->fail(new \Exception('Failed to determine available stock for specification ' . $this->model->hostGroup->hostSpec->ucs_specification_name));
                return false;
            }

            if (count($response) < 1) {
                $this->fail(new \Exception('Insufficient stock for specification ' . $this->model->hostGroup->hostSpec->ucs_specification_name));
                return false;
            }

            try {
                $response = $availabilityZone->conjurerService()->post(
                    '/api/v2/compute/' . $availabilityZone->ucs_compute_name . '/vpc/' . $hostGroup->vpc->id . '/host',
                    [
                        'json' => [
                            'specificationName' => $hostGroup->hostSpec->ucs_specification_name,
                            'hostId' => $this->model->id,
                        ],
                    ]
                );
                $response = json_decode($response->getBody()->getContents());
                $macAddress = collect($response->interfaces)->firstWhere('name', 'eth0')->address;
            } catch (\Exception $exception) {
                $error = ($exception instanceof RequestException && $exception->hasResponse()) ?
                    $exception->getResponse()->getBody()->getContents() :
                    $exception->getMessage();

                $this->fail(new \Exception('Failed to crate host profile: ' . $error));
                return false;
            }

            if (empty($macAddress)) {
                $this->fail(new \Exception('No MAC address returned for created host profile'));
                return false;
            }

            Log::debug('Host was created on UCS, MAC address: ' . $macAddress);
            $this->model->mac_address = $macAddress;
            $this->model->save();
        } finally {
            $lock->release();
        }
    }
}
