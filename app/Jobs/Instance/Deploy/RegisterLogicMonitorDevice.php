<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\Job;
use App\Models\V2\FloatingIp;
use App\Models\V2\Task;
use App\Traits\V2\LoggableTaskJob;
use Exception;
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

        if (count($device) > 0) {
            Log::info($this::class . ' : The device is already registered, skipping');
            return;
        }

        $floatingIp = FloatingIp::find($instance->deploy_data['floating_ip_id']);
        if (!$floatingIp) {
            $this->fail(new Exception('Failed to load floating IP ' . $instance->deploy_data['floating_ip_id'] . ' for instance ' . $instance->id));
            return;
        }

        if (empty($this->task->data['logic_monitor_account_id'])) {
            $this->fail(new Exception('Logic monitor account ID was not found in task data'));
            return;
        }

        // Load the collector for the availability zone the instance is deploying into
        $availabilityZone = $instance->availabilityZone;

        try {
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
                $this->fail(new Exception('Failed to load logic monitor credentials for instance ' . $instance->id));
                return;
            }
            $response = $adminMonitoringClient->devices()->createEntity(new Device([
                'reference_type' => 'server',
                'reference_id' => $instance->id,
                'collector_id' => $collector->id,
                'display_name' => $instance->id,
                'account_id' => $this->task->data['logic_monitor_account_id'],
                'ip_address' => $floatingIp->getIPAddress(),
                'snmp_community' => 'public',
                'platform' => $instance->platform,
                'username' => $logicMonitorCredentials->username,
                'password' => $logicMonitorCredentials->password,
            ]));
            $instance->save();
            Log::info($this::class . ' : Logic Monitor device registered : ' . $response->getId(), [
                'instance_id' => $instance->id
            ]);
        } catch (Exception $e) {
            Log::warning($this::class . ' : Logic Monitor device registration skipped', [
                'instance_id' => $instance->id,
                'reason' => $e->getMessage(),
            ]);
        }
    }
}
