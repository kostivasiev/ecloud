<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\Job;
use App\Models\V2\FloatingIp;
use App\Models\V2\Instance;
use App\Models\V2\IpAddress;
use App\Traits\V2\Jobs\AwaitTask;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class AssignFloatingIp extends Job
{
    use Batchable, LoggableModelJob, AwaitTask;

    private $model;

    public function __construct(Instance $instance)
    {
        $this->model = $instance;
    }

    public function handle()
    {
        if (empty($this->model->deploy_data['floating_ip_id'])) {
            Log::info(get_class($this) . ' : Floating IP assignment is not required, skipping');
            return;
        }

        $nic = $this->model->nics()->first(); // get ip address id from nic
        $ipAddress = $nic->ipAddresses()->withType(IpAddress::TYPE_DHCP)->first();

        $floatingIp = FloatingIp::findOrFail($this->model->deploy_data['floating_ip_id']);
        $task = $floatingIp->createTaskWithLock(
            'floating_ip_assign',
            \App\Jobs\Tasks\FloatingIp\Assign::class,
            ['resource_id' => $ipAddress->id]
        );

        Log::info('Triggered floating_ip_assign task for Floating IP (' . $floatingIp->id . '), assigning to NIC Address (' . $nic->id . ')');

        $this->awaitTask($task);
    }
}
