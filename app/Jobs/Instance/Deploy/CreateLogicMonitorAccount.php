<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\Job;
use App\Models\V2\Task;
use App\Traits\V2\LoggableTaskJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;
use UKFast\Admin\Monitoring\AdminClient as MonitoringAdminClient;
use UKFast\Admin\Account\AdminClient as AccountAdminClient;
use UKFast\Admin\Monitoring\Entities\Account;
use UKFast\SDK\Exception\NotFoundException;

class CreateLogicMonitorAccount extends Job
{
    use Batchable, LoggableTaskJob;

    private $task;

    public function __construct(Task $task)
    {
        $this->task = $task;
    }

    public function handle(MonitoringAdminClient $adminMonitoringClient, AccountAdminClient $accountAdminClient)
    {
        $instance = $this->task->resource;

        if (empty($instance->deploy_data['floating_ip_id'])) {
            Log::info($this::class . ' : No floating IP assigned to the instance, skipping');
            return;
        }

        $accounts = $adminMonitoringClient->setResellerId($instance->vpc->reseller_id)->accounts()->getAll();
        if (!empty($accounts)) {
            $this->task->updateData('logic_monitor_account_id', $accounts[0]->id);
            Log::info($this::class . ' : Logic Monitor account already exists, skipping', [
                'logic_monitor_account_id' => $accounts[0]->id
            ]);
            return;
        }

        try {
            $accountAdminClient->customers()->getById($instance->vpc->reseller_id);
        } catch (NotFoundException) {
            $this->fail(new \Exception('Failed to load account details for reseller_id ' . $instance->vpc->reseller_id));
            return;
        }

        $response = $adminMonitoringClient->accounts()->createEntity(new Account([
            'name' => $instance->vpc->reseller_id
        ]));

        $this->task->updateData('logic_monitor_account_id', $response->getId());
    }
}
