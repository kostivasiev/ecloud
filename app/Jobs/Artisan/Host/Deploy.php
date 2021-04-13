<?php

namespace App\Jobs\Artisan\Host;

use App\Jobs\Job;
use App\Models\V2\Host;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class Deploy extends Job
{
    use Batchable;

    private Host $host;

    public function __construct(Host $host)
    {
        $this->host = $host;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->host->id]);

        $availabilityZone = $this->host->hostGroup->availabilityZone;
        try {
            // Check if the host already exists on the SAN
            $response = $availabilityZone->artisanService()->get('/api/v2/san/' . $availabilityZone->san_name .'/host/' . $this->host->id);
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
            '/api/v2/compute/' . $availabilityZone->ucs_compute_name . '/vpc/' . $this->host->hostGroup->vpc->id .'/host/' . $this->host->id
        );
        $response = json_decode($response->getBody()->getContents());

        // Create the host on the SAN
        $availabilityZone->artisanService()->post(
            '/api/v2/san/' . $availabilityZone->san_name . '/host',
            [
                'json' => [
                    'hostId' => $this->host->id,
                    'fcWWNs' => collect($response->interfaces)->filter(function ($value) {
                        return $value->type == 'vHBA';
                    })->pluck('address')->toArray(),
                    'osType' => 'VMWare'
                ],
            ]
        );

        Log::info(get_class($this) . ' : Finished', ['id' => $this->host->id]);
    }
}
