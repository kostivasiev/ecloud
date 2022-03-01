<?php
namespace App\Jobs\VpnSession;

use App\Jobs\Job;
use App\Jobs\TaskJob;
use App\Models\V2\VpnSession;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;

class DeletePreSharedKey extends TaskJob
{
    public function handle()
    {
        $this->task->resource->credentials()->delete();
    }
}
