<?php

namespace App\Jobs\Sync\Host;

use App\Jobs\Artisan\Host\Deploy;
use App\Jobs\Conjurer\Host\CheckAvailableCompute;
use App\Jobs\Conjurer\Host\CreateAutoDeployRule;
use App\Jobs\Conjurer\Host\CreateLanPolicy;
use App\Jobs\Conjurer\Host\CreateProfile;
use App\Jobs\Conjurer\Host\PowerOn;
use App\Jobs\Job;
use App\Jobs\Kingpin\Host\CheckOnline;
use App\Models\V2\Sync;
use App\Traits\V2\SyncableBatch;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

class Update extends Job
{
    use SyncableBatch;

    private $sync;

    public function __construct(Sync $sync)
    {
        $this->sync = $sync;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->sync->id, 'resource_id' => $this->sync->resource->id]);

        $host = $this->sync->resource;
        $vpc = $host->hostGroup->vpc;
        $availabilityZone = $host->hostGroup->availabilityZone;

        $deployed = true;
        // Only create if the host doesnt already exist
        try {
            $availabilityZone->conjurerService()->get(
                '/api/v2/compute/' . $availabilityZone->ucs_compute_name . '/vpc/' . $vpc->id . '/host/' . $host->id
            );
        } catch (RequestException $exception) {
            if ($exception->getCode() == 404) {
                $deployed = false;
            } else {
                throw $exception;
            }
        }

        if (!$deployed) {
            $this->updateSyncBatch([
                [
                    new CreateLanPolicy($host),
                    new CheckAvailableCompute($host),
                    new CreateProfile($host),
                    new CreateAutoDeployRule($host),
                    new Deploy($host),
                    new PowerOn($host),
                    new CheckOnline($host),
                ],
            ])->dispatch();
        } else {
            $this->sync->completed = true;
            $this->sync->save();
        }

        Log::info(get_class($this) . ' : Finished', ['id' => $this->sync->id, 'resource_id' => $this->sync->resource->id]);
    }
}
