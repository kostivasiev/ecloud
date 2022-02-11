<?php

namespace App\Jobs\LoadBalancer;

use App\Jobs\TaskJob;
use UKFast\Admin\Loadbalancers\AdminClient;
use UKFast\SDK\Exception\ServerException;

class ConfigurePeers extends TaskJob
{
    public function handle()
    {
        $loadbalancer = $this->task->resource;
        $client = app()->make(AdminClient::class)
            ->setResellerId($loadbalancer->getResellerId());
        try {
            $client->clusters()->configurePeers($loadbalancer->config_id);
        } catch (\Exception $exception) {
            if ($exception->getMessage() !== 'Bad Gateway') {
                $this->fail($exception);
            }
        }
    }
}
