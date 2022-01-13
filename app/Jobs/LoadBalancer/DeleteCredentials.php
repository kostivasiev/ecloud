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
            Log::info('No credentials to cleanup, skipping', ['id' => $loadbalancer->id]);
            return;
        }
        $loadbalancer->credentials->each(function ($credential) {
            $credential->delete();
        });
    }
}
