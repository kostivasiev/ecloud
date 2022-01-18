<?php

namespace App\Jobs\LoadBalancer;

use App\Jobs\TaskJob;
use App\Services\V2\PasswordService;

class CreateCredentials extends TaskJob
{
    public function handle(PasswordService $passwordService)
    {
        $loadbalancer = $this->task->resource;

        $loadbalancer->credentials()->firstOrCreate(
            ['username' => 'keepalived'],
            [
                'name' => 'keepalived',
                'host' => null,
                'password' => $passwordService->generate(8),
                'port' => null,
                'is_hidden' => true,
            ]
        );

        $loadbalancer->credentials()->firstOrCreate(
            ['username' => 'ukfast_stats'],
            [
                'name' => 'haproxy stats',
                'host' => null,
                'password' => $passwordService->generate(),
                'port' => 8090,
                'is_hidden' => true,
            ]
        );
    }
}
