<?php

namespace App\Jobs\Conjurer\Host;

use App\Jobs\Job;
use App\Models\V2\Host;
use App\Traits\V2\LoggableModelJob;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class CreateLanPolicy extends Job
{
    use Batchable, LoggableModelJob;

    private Host $model;

    public function __construct(Host $host)
    {
        $this->model = $host;
    }

    /**
     * @return bool
     */
    public function handle()
    {
        $vpc = $this->model->hostGroup->vpc;
        $availabilityZone = $this->model->hostGroup->availabilityZone;

        if (empty($availabilityZone->ucs_compute_name)) {
            $this->fail(new \Exception('Failed to load UCS compute name for availability zone ' . $availabilityZone->id));
            return false;
        }

        // Check whether a LAN connectivity policy exists on the UCS for the VPC
        try {
            $availabilityZone->conjurerService()->get('/api/v2/compute/' . $availabilityZone->ucs_compute_name . '/vpc/' . $vpc->id);

            Log::debug('LAN connectivity policy already exists, skipping');
            return true;
        } catch (RequestException $exception) {
            if ($exception->hasResponse() && $exception->getResponse()->getStatusCode() != 404) {
                throw $exception;
            }
        }

        $availabilityZone->conjurerService()->post(
            '/api/v2/compute/' . $availabilityZone->ucs_compute_name . '/vpc',
            [
                'json' => [
                    'vpcId' => $vpc->id,
                ],
            ]
        );
        Log::info(get_class($this) . ' : LAN policy created on UCS for VPC', ['id' => $this->model->id]);
    }
}
