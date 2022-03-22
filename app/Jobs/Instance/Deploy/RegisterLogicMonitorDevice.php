<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\Job;
use App\Models\V2\Instance;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;
use UKFast\Admin\Monitoring\AdminClient;
use UKFast\SDK\Exception\NotFoundException;

class RegisterLogicMonitorDevice extends Job
{
    use Batchable, LoggableModelJob;

    private $model;

    public function __construct(Instance $instance)
    {
        $this->model = $instance;
    }

    public function handle(AdminClient $adminMonitoringClient)
    {
        $instance = $this->model;

        if (empty($this->model->deploy_data['floating_ip_id'])) {
            Log::info(get_class($this) . ' : No floating IP assigned to the instance, skipping');
            return;
        }

        // When the instance is already registered | Then the job skips as no further action required
        try {
            $device = $adminMonitoringClient->devices()->getById('vcentre-vm', $instance->id);
            if (!empty($device)) {
                Log::info();
            }
        } catch (NotFoundException) {
            // device does not exist
        }

    // When the instance is NOT registered | Then load the target collector for the AZ the instance is deploying into

    // When the reseller is NOT registered | Then load the reseller name and create an account

    // When the register device endpoint is called | Then provide the instances name, id, 'eCloud', platform, ip, tier, collectorId, creds

    }
}
