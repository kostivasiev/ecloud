<?php

namespace App\Jobs\Router;

use App\Jobs\Job;
use App\Models\V2\Dhcp;
use App\Models\V2\Router;
use App\Support\Sync;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class AwaitDhcpSync extends Job
{
    use Batchable, LoggableModelJob;

    private Router $model;

    public function __construct(Router $router)
    {
        $this->model = $router;
    }

    public function handle()
    {
        $availabilityZone = $this->model->availabilityZone;
        $vpc = $this->model->vpc;
        $dhcp = $vpc->dhcps()->where('availability_zone_id', $availabilityZone->id)->get()->first();

        if ($dhcp->sync->status == Sync::STATUS_FAILED) {
            Log::error('DHCP in failed sync state, abort', ['id' => $this->model->id, 'dhcp' => $dhcp->id]);
            $this->fail(new \Exception("DHCP '" . $dhcp->id . "' in failed sync state"));
            return;
        }

        if ($dhcp->sync->status != Sync::STATUS_COMPLETE) {
            Log::warning('DHCP not in sync, retrying in ' . $this->backoff . ' seconds', ['id' => $this->model->id, 'dhcp' => $dhcp->id]);
            return $this->release($this->backoff);
        }
    }
}
