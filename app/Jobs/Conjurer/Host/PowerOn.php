<?php

namespace App\Jobs\Conjurer\Host;

use App\Jobs\Job;
use App\Models\V2\Host;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class PowerOn extends Job
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

        $availabilityZone->conjurerService()->post(
            '/api/v2/compute/' . $availabilityZone->ucs_compute_name . '/vpc/' . $this->host->hostGroup->vpc->id .'/host/' . $this->host->id . '/power'
        );

        Log::info(get_class($this) . ' : Finished', ['id' => $this->host->id]);
    }
}
