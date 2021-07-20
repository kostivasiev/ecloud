<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\Job;
use App\Models\V2\FloatingIp;
use App\Models\V2\Instance;
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

        $nic = $this->model->nics()->first();

        $floatingIp = FloatingIp::findOrFail($this->model->deploy_data['floating_ip_id']);
        $task = $floatingIp->createTaskWithLock(
            'floating_ip_assign',
            \App\Jobs\Tasks\FloatingIp\Assign::class,
            ['resource_id' => $nic->id]
        );

        Log::info('Triggered floating_ip_assign job for Floating IP (' . $floatingIp->id . '), assigning to NIC (' . $nic->id . ')');

        $this->awaitTask($task);
    }
}
