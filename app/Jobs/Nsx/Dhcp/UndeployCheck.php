<?php

namespace App\Jobs\Nsx\Dhcp;

use App\Jobs\Job;
use App\Models\V2\Dhcp;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class UndeployCheck extends Job
{
    use Batchable;

    const RETRY_MAX = 60;
    const RETRY_DELAY = 5;

    private Dhcp $dhcp;

    public function __construct(Dhcp $dhcp)
    {
        $this->dhcp = $dhcp;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->dhcp->id]);

        if ($this->attempts() > static::RETRY_MAX) {
            throw new \Exception('Failed waiting for ' . $this->dhcp->id . ' to be deleted after ' . static::RETRY_MAX . ' attempts');
        }

        $response = $this->dhcp->availabilityZone->nsxService()->get(
            '/policy/api/v1/infra/dhcp-server-configs/?include_mark_for_delete_objects=true'
        );
        $response = json_decode($response->getBody()->getContents());
        foreach ($response->results as $result) {
            if ($this->dhcp->id === $result->id) {
                Log::info(
                    'Waiting for ' . $this->dhcp->id . ' to be deleted, retrying in ' . static::RETRY_DELAY . ' seconds'
                );

                return $this->release(static::RETRY_DELAY);
            }
        }

        Log::info(get_class($this) . ' : Finished', ['id' => $this->dhcp->id]);
    }
}
