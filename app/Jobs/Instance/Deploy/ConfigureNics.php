<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\ConfigureNic;
use App\Jobs\Job;
use App\Models\V2\Instance;
use App\Models\V2\Network;
use Exception;
use Illuminate\Support\Facades\Log;

class ConfigureNics extends Job
{
    private $data;

    const RETRY_ATTEMPTS = 10;

    const RETRY_DELAY = 10;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function handle()
    {
        Log::info('Performing ConfigureNics for instance ' . $this->data['instance_id']);

        $instance = Instance::findOrFail($this->data['instance_id']);

        $networks = Network::whereHas('nics.instance', function ($query) use ($instance) {
            $query->where('id', '=', $instance->getKey());
        })->get();

        foreach ($networks as $network) {
            if (!$network->available) {
                if ($this->attempts() <= static::RETRY_ATTEMPTS) {
                    $this->release(static::RETRY_DELAY);
                    Log::info('Attempted to configure NICs on Network (' . $network->getKey() .
                        ') but Network was not available, will retry shortly');
                    return;
                } else {
                    $message = 'Timed out waiting for Network (' . $network->getKey() .
                        ') to become available for prior to NIC configuration';
                    Log::error($message);
                    $this->fail(new Exception($message));
                    return;
                }
            }
        }

        $instanceNics = $instance->nics()
            ->whereNotNull('network_id')
            ->where('network_id', '!=', '')
            ->get();

        foreach ($instanceNics as $nic) {
            dispatch((new ConfigureNic($nic)));
        }
    }
}
