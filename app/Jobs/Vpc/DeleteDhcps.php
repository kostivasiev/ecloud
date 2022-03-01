<?php

namespace App\Jobs\Vpc;

use App\Jobs\Job;
use App\Jobs\TaskJob;
use App\Models\V2\Vpc;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;

class DeleteDhcps extends TaskJob
{
    /**
     * @return bool
     */
    public function handle()
    {
        $vpc = $this->task->resource;

        $vpc->dhcps()->each(function ($dhcp) {
            $this->info('Trigger sync delete for DHCP ' . $dhcp->id);
            $dhcp->syncDelete();
        });

        return true;
    }
}
