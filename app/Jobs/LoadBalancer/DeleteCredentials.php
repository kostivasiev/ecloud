<?php

namespace App\Jobs\LoadBalancer;

use App\Jobs\TaskJob;
use Illuminate\Support\Facades\Log;

class DeleteCredentials extends TaskJob
{
    public function handle()
    {
        $loadbalancer = $this->task->resource;
        if ($loadbalancer->credentials()->count() == 0) {
            $this->info('No credentials to delete, skipping');
            return;
        }
        $loadbalancer->credentials->each(function ($credential) {
            $credential->delete();
        });
    }
}
