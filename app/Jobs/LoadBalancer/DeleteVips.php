<?php
namespace App\Jobs\LoadBalancer;

use App\Jobs\Job;
use App\Models\V2\LoadBalancer;
use App\Models\V2\Task;
use App\Models\V2\Vip;
use App\Traits\V2\Jobs\AwaitResources;
use App\Traits\V2\Jobs\AwaitTask;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;

class DeleteVips extends Job
{

    use Batchable, LoggableModelJob, AwaitResources, AwaitTask;

    private LoadBalancer $model;

    private Task $task;

    public function __construct(Task $task)
    {
        $this->task = $task;
        $this->model = $this->task->resource;
    }

    /**
     * @throws \Exception
     */
    public function handle()
    {
        $loadBalancer = $this->model;
        if (empty($this->task->data['vip_ids'])) {
            $vipIds = [];
            $loadBalancer->vips()->each(function ($vip) use (&$vipIds) {
                $vip->syncDelete();
                $vipIds[] = $vip->id;
            });
            $this->task->setAttribute('data', [
                'vip_ids' => $vipIds
            ])->saveQuietly();
        } else {
            $vipIds = Vip::whereIn('id', $this->task->data['vip_ids'])
                ->get()
                ->pluck('id')
                ->toArray();
        }
        $this->awaitSyncableResources($vipIds);
    }
}