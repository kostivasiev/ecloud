<?php

namespace App\Jobs\Instance\Undeploy;

use App\Jobs\Job;
use App\Models\V2\FloatingIp;
use App\Models\V2\Instance;
use App\Models\V2\IpAddress;
use App\Traits\V2\Jobs\AwaitTask;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class UnassignFloatingIP extends Job
{
    use Batchable, LoggableModelJob, AwaitTask;
    
    private $model;

    private $taskIds = [];

    public function __construct(Instance $instance)
    {
        $this->model = $instance;
    }

    public function handle()
    {
        $instance = $this->model;

        $instance->nics()->each(function ($nic) {
            //-- TODO: Delete this after we have run the artisan script to migrate fips from NICs to ipAddress!
            if ($nic->floatingIp()->exists()) {
                $this->taskIds[] = $nic->floatingIp->createTaskWithLock(
                    'floating_ip_unassign',
                    \App\Jobs\Tasks\FloatingIp\Unassign::class
                );
                Log::info('Triggered floating_ip_unassign task for Floating IP (' . $nic->floatingIp->id . ')');
            }
            //--

            $nic->ipAddresses()->each(function ($ipAddress) {
                if ($ipAddress->floatingIp()->exists()) {
                    $this->taskIds[] = ($ipAddress->floatingIp->createTaskWithLock(
                        'floating_ip_unassign',
                        \App\Jobs\Tasks\FloatingIp\Unassign::class
                    ))->id;

                    Log::info('Triggered floating_ip_unassign task for Floating IP (' . $ipAddress->floatingIp->id . ')');
                }
            });
        });

        if (!empty($this->taskIds)) {
            $this->awaitTasks($this->taskIds);
        }
    }
}
