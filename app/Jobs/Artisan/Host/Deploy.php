<?php

namespace App\Jobs\Artisan\Host;

use App\Jobs\Job;
use App\Models\V2\Host;
use App\Traits\V2\LoggableModelJob;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class Deploy extends Job
{
    use Batchable, LoggableModelJob;

    private Host $model;

    public function __construct(Host $host)
    {
        $this->model = $host;
    }

    public function handle()
    {
        $availabilityZone = $this->model->hostGroup->availabilityZone;
        try {
            // Check if the host already exists on the SAN
            $response = $availabilityZone->artisanService()->get('/api/v2/san/' . $availabilityZone->san_name .'/host/' . $this->model->id);
            if ($response->getStatusCode() == 200) {
                Log::info(get_class($this) . ' : Host already exists on the SAN, nothing to do.', ['id' => $this->model->id]);
                return true;
            }
        } catch (RequestException $exception) {
            if ($exception->getCode() != 404) {
                throw $exception;
            }
        }

        // Load the host profile from the UCS
        $response = $availabilityZone->conjurerService()->get(
            '/api/v2/compute/' . $availabilityZone->ucs_compute_name . '/vpc/' . $this->model->hostGroup->vpc->id .'/host/' . $this->model->id
        );
        $response = json_decode($response->getBody()->getContents());

        // Create the host on the SAN
        $availabilityZone->artisanService()->post(
            '/api/v2/san/' . $availabilityZone->san_name . '/host',
            [
                'json' => [
                    'hostId' => $this->model->id,
                    'fcWWNs' => collect($response->interfaces)->filter(function ($value) {
                        return $value->type == 'vHBA';
                    })->pluck('address')->toArray(),
                    'osType' => 'VMWare'
                ],
            ]
        );
    }
}
