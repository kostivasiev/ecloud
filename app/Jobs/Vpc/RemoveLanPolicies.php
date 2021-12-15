<?php

namespace App\Jobs\Vpc;

use App\Jobs\TaskJob;
use GuzzleHttp\Exception\RequestException;

class RemoveLanPolicies extends TaskJob
{
    /**
     * @return bool
     */
    public function handle()
    {
        $vpc = $this->task->resource;

        foreach ($vpc->region->availabilityZones as $availabilityZone) {
            if (empty($availabilityZone->ucs_compute_name)) {
                $this->fail(new \Exception('Failed to load UCS compute name for availability zone ' . $availabilityZone->id));
                return false;
            }

            // Check whether a LAN connectivity policy exists on the UCS for the VPC
            try {
                $availabilityZone->conjurerService()->get('/api/v2/compute/' . $availabilityZone->ucs_compute_name . '/vpc/' .  $vpc->id);
            } catch (RequestException $exception) {
                if ($exception->hasResponse() && $exception->getResponse()->getStatusCode() == 404) {
                    $this->warning('LAN policy doesn\'t exist on UCS for VPC', ['availability_zone_id' => $availabilityZone->id]);
                    continue;
                }

                throw $exception;
            }

            $this->info('Removing LAN policy from UCS for VPC', ['availability_zone_id' => $availabilityZone->id]);
            $availabilityZone->conjurerService()->delete(
                '/api/v2/compute/' . $availabilityZone->ucs_compute_name . '/vpc/' .  $vpc->id
            );
            $this->info('LAN policy removed from UCS for VPC', ['availability_zone_id' => $availabilityZone->id]);
        }
    }
}
