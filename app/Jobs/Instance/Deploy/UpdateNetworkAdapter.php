<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\TaskJob;
use App\Models\V2\Instance;
use App\Models\V2\Task;
use App\Models\V2\Vpc;
use Illuminate\Support\Facades\Log;

class UpdateNetworkAdapter extends TaskJob
{
    private $data;

    public function __construct(Task $task, $data)
    {
        parent::__construct($task);

        $this->data = $data;
    }

    /**
     * @see https://gitlab.devops.ukfast.co.uk/ukfast/api.ukfast/ecloud/-/issues/327
     */
    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['data' => $this->data]);

        $instance = Instance::findOrFail($this->data['instance_id']);
        $vpc = Vpc::findOrFail($this->data['vpc_id']);

        if (empty($instance->applianceVersion->appliance_version_vm_template)) {
            Log::info('Skipped UpdateNetworkAdapter for instance ' . $this->data['instance_id']);
            return;
        }

        foreach ($instance->nics as $nic) {
            $instance->availabilityZone->kingpinService()->put(
                '/api/v2/vpc/' . $vpc->id . '/instance/' . $instance->id . '/nic/' . $nic->mac_address . '/connect',
                [
                    'json' => [
                        'networkId' => $nic->network_id,
                    ],
                ]
            );
        }

        Log::info(get_class($this) . ' : Finished', ['data' => $this->data]);
    }
}
