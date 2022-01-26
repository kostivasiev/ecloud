<?php
namespace App\Jobs\LoadBalancer;

use App\Jobs\TaskJob;
use App\Models\V2\Vip;
use App\Traits\V2\TaskJobs\AwaitResources;

class DeleteVips extends TaskJob
{

    use AwaitResources;

    /**
     * @throws \Exception
     */
    public function handle()
    {
        $loadBalancer = $this->task->resource;
        if (empty($this->task->data['vip_ids'])) {
            $vipIds = [];
            $loadBalancer->vips()->each(function ($vip) use (&$vipIds) {
                $vip->syncDelete();
                $vipIds[] = $vip->id;
            });
            $this->task->updateData('vip_ids', $vipIds);
        } else {
            $vipIds = Vip::whereIn('id', $this->task->data['vip_ids'])
                ->get()
                ->pluck('id')
                ->toArray();
        }
        $this->awaitSyncableResources($vipIds);
    }
}
