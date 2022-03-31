<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\Job;
use App\Models\V2\FloatingIp;
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

        $floatingIp = FloatingIp::find($instance->deploy_data['floating_ip_id']);
        if (!$floatingIp) {
            $this->fail(new \Exception('Failed to load floating IP for instance', [
                'instance_id' => $instance->id,
                'floating_ip_id' => $instance->deploy_data['floating_ip_id']
            ]));
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

        // Load the collector for the availability zone the instance is deploying into
        $availabilityZone = $instance->availabilityZone;

        $collectorsPage = $adminMonitoringClient->collectors()->getPage(1, 15, [
            'datacentre_id' => $availabilityZone->datacentre_site_id,
            'is_shared' => true
        ]);

        if ($collectorsPage->totalItems() < 1) {
            Log::warning('Failed to load logic monitor collector for availability zone ' . $availabilityZone->id);
            return;
        }

        $collector = $collectorsPage->getItems()[0];

        $logicMonitorCredentials = $instance->credentials()
            ->where('username', 'lm.' . $instance->id)
            ->first();
        if (!$logicMonitorCredentials) {
            $this->fail(new \Exception('Failed to load logic monitor credentials for instance ' . $instance->id));
            return;
        }

        // TODO: REMOVE THIS
        Log::debug(['reference_type' => 'server',
            'reference_id' => $instance->id,
            'collector_id' => $collector->id,
            'display_name' => $instance->name,
            'tier_id' => '8485a243-8a83-11ec-915e-005056ad1662', // This is the free tier from Monitoring APIO
            'account_id' => $instance->deploy_data['logic_monitor_account_id'],
            'ip_address' => $floatingIp->getIPAddress(),
            'snmp_community' => 'public',
            'platform' => $instance->platform,
            'username' => $logicMonitorCredentials->username,
            'password' => $logicMonitorCredentials->password]);

        $response = $adminMonitoringClient->devices()->createEntity(new Device([
            'reference_type' => 'server',
            'reference_id' => $instance->id,
            'collector_id' => $collector->id,
            'display_name' => $instance->name,
            'tier_id' => '8485a243-8a83-11ec-915e-005056ad1662', // This is the free tier from Monitoring APIO
            'account_id' => $instance->deploy_data['logic_monitor_account_id'],
            'ip_address' => $floatingIp->getIPAddress(),
            'snmp_community' => 'public',
            'platform' => $instance->platform,
            'username' => $logicMonitorCredentials->username,
            'password' => $logicMonitorCredentials->password,
        ]));

        $deploy_data = $instance->deploy_data;
        $deploy_data['logic_monitor_device_id'] = $response->getId();
        $instance->deploy_data = $deploy_data;
        $instance->save();

        Log::info($this::class . ' : Logic Monitor device registered : ' . $deploy_data['logic_monitor_device_id'], [
            'instance_id' => $instance->id
        ]);
    }
}
