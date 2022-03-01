<?php


namespace App\Jobs\Vpc;

use App\Jobs\TaskJob;
use App\Support\Sync;

class AwaitDhcpRemoval extends TaskJob
{
    public $tries = 30;
    public $backoff = 5;

    public function handle()
    {
        $vpc = $this->task->resource;

        if ($vpc->dhcps()->count() > 0) {
            foreach ($vpc->dhcps as $dhcp) {
                if ($dhcp->sync->status == Sync::STATUS_FAILED) {
                    $this->error('DHCP in failed sync state, abort', ['dhcp' => $dhcp->id]);
                    $this->fail(new \Exception("DHCP '" . $dhcp->id . "' in failed sync state"));
                    return;
                }
            }

            $this->warning($vpc->dhcps()->count() . ' DHCP(s) still attached, retrying in ' . $this->backoff . ' seconds');

            $this->release($this->backoff);
        }
    }
}
