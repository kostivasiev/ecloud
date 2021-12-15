<?php

namespace App\Jobs\Router;

use App\Jobs\TaskJob;
use App\Support\Sync;

class AwaitDhcpSync extends TaskJob
{
    public $tries = 30;
    public $backoff = 5;

    public function handle()
    {
        $router = $this->task->resource;

        $availabilityZone = $router->availabilityZone;
        $vpc = $router->vpc;
        $dhcp = $vpc->dhcps()->where('availability_zone_id', $availabilityZone->id)->get()->first();

        if ($dhcp->sync->status == Sync::STATUS_FAILED) {
            $this->error('DHCP in failed sync state, abort', ['dhcp' => $dhcp->id]);
            $this->fail(new \Exception("DHCP '" . $dhcp->id . "' in failed sync state"));
            return;
        }

        if ($dhcp->sync->status != Sync::STATUS_COMPLETE) {
            $this->warning('DHCP not in sync, retrying in ' . $this->backoff . ' seconds', ['dhcp' => $dhcp->id]);
            return $this->release($this->backoff);
        }
    }
}
