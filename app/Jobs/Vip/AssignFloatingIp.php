<?php

namespace App\Jobs\Vip;

use App\Jobs\Job;
use App\Models\V2\FloatingIp;
use App\Models\V2\Task;
use App\Models\V2\Vip;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;

class AssignFloatingIp extends Job
{
    use Batchable, LoggableModelJob;

    private Vip $model;
    private Task $task;

    public function __construct(Task $task)
    {
        $this->task = $task;
        $this->model = $task->resource;
    }

    public function handle()
    {
        $vip = $this->model;

        if ($this->task->data['allocate_floating_ip'] != true) {
            return;
        }

        exit(print_r(
           'here'
        ));

//        // assign floating ip to the cluster ip of the vip
//
//        $ipAddress = $vip->ipAddress;
//
//        $floatingIp = app()->make(FloatingIp::class);
//        $floatingIp->vpc_id = $vip->network->router->vpc->id;
//        $floatingIp->availability_zone_id = $this->model->availability_zone_id;
//        $floatingIp->syncSave();
//
//
//        $task = $floatingIp->createTaskWithLock(
//            'floating_ip_assign',
//            \App\Jobs\Tasks\FloatingIp\Assign::class,
//            ['resource_id' => $request->resource_id]
//        );

    }
}
