<?php

namespace App\Jobs\Sync\Host;

use App\Jobs\Job;
use App\Models\V2\Host;
use App\Models\V2\Sync;
use App\Traits\V2\SyncableBatch;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

class Delete extends Job
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

        if ($this->checkExists($host)) {
            $this->deleteSyncBatch([
                new \App\Jobs\Kingpin\Host\MaintenanceMode($host),
                new \App\Jobs\Kingpin\Host\DeleteInVmware($host),
                new \App\Jobs\Conjurer\Host\PowerOff($host),
                new \App\Jobs\Artisan\Host\RemoveFrom3Par($host),
                new \App\Jobs\Conjurer\Host\DeleteServiceProfile($host),
            ])->dispatch();
        }

        Log::info(get_class($this) . ' : Finished', ['id' => $host->id]);
    }

    public function checkExists(Host $host)
    {
        $availabilityZone = $host->hostGroup->availabilityZone;
        try {
            $response = $availabilityZone->kingpinService()->get(
                '/api/v2/san/' . $availabilityZone->san_name . '/host/' . $host->id
            );
        } catch (RequestException $exception) {// handle 40x/50x response if host not found
            if ($exception->getCode() != 404) {
                throw $exception;
            }
            Log::warning(get_class($this) . ' : Host does not exist, skipping.');
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
        return true;
    }
}
