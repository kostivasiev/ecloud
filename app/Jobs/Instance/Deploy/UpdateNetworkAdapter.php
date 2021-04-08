<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\Job;
use App\Models\V2\Instance;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class UpdateNetworkAdapter extends Job
{
    use Batchable;

    private $instance;

    public function __construct(Instance $instance)
    {
        $this->instance = $instance;
    }

    /**
     * @see https://gitlab.devops.ukfast.co.uk/ukfast/api.ukfast/ecloud/-/issues/327
     */
    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->instance->id]);

        if (empty($this->instance->image->vm_template_name)) {
            Log::info('Skipped UpdateNetworkAdapter for instance ' . $this->instance->id . ': no vm template found');
            return;
        }

        foreach ($this->instance->nics as $nic) {
            $this->instance->availabilityZone->kingpinService()->put(
                '/api/v2/vpc/' . $this->instance->vpc->id . '/instance/' . $this->instance->id . '/nic/' . $nic->mac_address . '/connect',
                [
                    'json' => [
                        'networkId' => $nic->network_id,
                    ],
                ]
            );
        }

        Log::info(get_class($this) . ' : Finished', ['id' => $this->instance->id]);
    }
}
