<?php

namespace App\Jobs\Network;

use App\Jobs\TaskJob;

class DeleteNetworkPolicy extends TaskJob
{
    public function handle()
    {
        $network = $this->task->resource;
        
        if ($network->networkPolicy()->exists()) {
            $network->networkPolicy->syncDelete();
        }
    }
}
