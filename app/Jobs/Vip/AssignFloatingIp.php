<?php

namespace App\Jobs\Vip;

use App\Jobs\TaskJob;
use App\Models\V2\FloatingIp;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;

class AssignFloatingIp extends TaskJob
{

    /**
     * If I request a fIP then the NAT exists to the VIP, the fIP is locked from deletion and is only removed on vip deletion (for mvp)
     * @return void
     */
    public function handle()
    {
        $vip = $this->task->resource;

        if ($this->task->data['allocate_floating_ip'] != true) {
            return;
        }

        exit(print_r(
           'here'
        ));

        // assign floating ip to the cluster ip of the vip

        $ipAddress = $vip->ipAddress;

        $floatingIp = app()->make(FloatingIp::class);
        $floatingIp->vpc_id = $vip->network->router->vpc->id;
        $floatingIp->availability_zone_id = $this->model->availability_zone_id;
        $floatingIp->syncSave();

        // Todo: await sync


        $task = $floatingIp->createTaskWithLock(
            'floating_ip_assign',
            \App\Jobs\Tasks\FloatingIp\Assign::class,
            ['resource_id' => $request->resource_id]
        );

        //Todo: await task

    }
}
