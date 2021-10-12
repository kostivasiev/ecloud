<?php

namespace App\Jobs\Vpc;

use App\Jobs\Job;
use App\Models\V2\Vpc;
use App\Traits\V2\LoggableModelJob;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class RemoveLanPolicies extends Job
{
    use Batchable, LoggableModelJob;

    private Vpc $model;

    public function __construct(Vpc $vpc)
    {
        $this->model = $vpc;
    }

    /**
     * @return bool
     */
    public function handle()
    {
        foreach ($this->model->region->availabilityZones as $availabilityZone) {
            if (empty($availabilityZone->ucs_compute_name)) {
                $this->fail(new \Exception('Failed to load UCS compute name for availability zone ' . $availabilityZone->id));
                return false;
            }

            // Check whether a LAN connectivity policy exists on the UCS for the VPC
            try {
                $availabilityZone->conjurerService()->get('/api/v2/compute/' . $availabilityZone->ucs_compute_name . '/vpc/' . $this->model->id);
            } catch (RequestException $exception) {
                if ($exception->hasResponse() && $exception->getResponse()->getStatusCode() == 404) {
                    Log::warning(get_class($this) .  ' : LAN policy doesn\'t exist on UCS for VPC', ['id' => $this->model->id, 'availability_zone_id' => $availabilityZone->id]);
                    continue;
                }

                throw $exception;
            }

            Log::info(get_class($this) .  ' : Removing LAN policy from UCS for VPC', ['id' => $this->model->id, 'availability_zone_id' => $availabilityZone->id]);
            $availabilityZone->conjurerService()->delete(
                '/api/v2/compute/' . $availabilityZone->ucs_compute_name . '/vpc/' . $this->model->id
            );
            Log::info(get_class($this) . ' : LAN policy removed from UCS for VPC', ['id' => $this->model->id, 'availability_zone_id' => $availabilityZone->id]);
        }
    }
}
