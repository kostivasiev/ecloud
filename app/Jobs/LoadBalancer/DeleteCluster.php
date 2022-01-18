<?php

namespace App\Jobs\LoadBalancer;

use App\Jobs\TaskJob;
use App\Traits\V2\TaskJobs\AwaitResources;
use Illuminate\Support\Facades\Log;
use UKFast\Admin\Loadbalancers\AdminClient;

class DeleteCluster extends TaskJob
{
    use AwaitResources;

    public function handle()
    {
        $loadBalancer = $this->task->resource;
        if ($loadBalancer->config_id === null) {
            $this->info('No Loadbalancer Cluster available, skipping');
            return;
        }
        $client = app()->make(AdminClient::class)
            ->setResellerId($loadBalancer->getResellerId());
        $status = $client->clusters()->deleteById($loadBalancer->config_id);
        if ($status) {
            Log::info('Loadbalancer cluster has been deleted.', [
                'id' => $loadBalancer->id,
                'cluster_id' => $loadBalancer->config_id,
            ]);
        }
    }
}
