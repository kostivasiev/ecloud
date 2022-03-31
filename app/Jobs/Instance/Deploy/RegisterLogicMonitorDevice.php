<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\Job;
use App\Models\V2\Task;
use App\Traits\V2\LoggableTaskJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;
use UKFast\Admin\Monitoring\AdminClient;
use UKFast\Admin\Monitoring\Entities\Device;

class RegisterLogicMonitorDevice extends Job
{
    use Batchable, LoggableTaskJob;

    private $task;

    public function __construct(Task $task)
    {
        $this->task = $task;
    }

    public function handle(AdminClient $adminMonitoringClient)
    {
        $instance = $this->task->resource;

        if (empty($instance->deploy_data['floating_ip_id'])) {
            Log::info(get_class($this) . ' : No floating IP assigned to the instance, skipping');
            return;
        }

        $device = $adminMonitoringClient->devices()->getAll([
            'reference_type' => 'server',
            'reference_id:eq' => $instance->id
        ]);

        if (!empty($device)) {
            Log::info($this::class . ' : The device is already registered, skipping');
            return;
        }

        $availabilityZone = $instance->availabilityZone;

        // Load the collector for the availability zone
        $collectors = $adminMonitoringClient->collectors()->getPage(1, 1, [
            'datacentre_id' => $availabilityZone->datacentre_site_id,
            'is_shared' => true
        ]);

        if ($collectors->totalItems() < 1) {
            Log::warning('Failed to load logic monitor collector for availability zone ' . $availabilityZone->id);
            return;
        }



        // When the register device endpoint is called | Then provide the instances name, id, 'eCloud', platform, ip, tier, collectorId, creds
        // load the target collector for the AZ the instance is deploying into
        $device = new Device([
            'reference_type' => 'server',
            'reference_id' => $instance->id,


            'collector_id' => '', //TODO: "ID of the shared collector for our instances DC",

            'display_name' => $instance->name,
            'tier_id' => '8485a243-8a83-11ec-915e-005056ad1662', // TODO, this is the free tier from Monitoring dev - is the UUID the same on prod?


            'account_id' => $instance->vpc->reseller_id,


        ]);



    }
}
