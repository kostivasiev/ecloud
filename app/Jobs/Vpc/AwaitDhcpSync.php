<?php

namespace App\Jobs\Vpc;

use App\Jobs\Job;
use App\Models\V2\Sync;
use App\Models\V2\Vpc;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class AwaitDhcpSync extends Job
{
    use Batchable;

    public $tries = 30;
    public $backoff = 5;

    private Vpc $vpc;

    public function __construct(Vpc $vpc)
    {
        $this->vpc = $vpc;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->vpc->id]);

        $this->vpc->dhcps()->each(function ($dhcp) {
            if ($dhcp->sync->status == Sync::STATUS_FAILED) {
                Log::error('DHCP in failed sync state, abort', ['id' => $this->vpc->id, 'dhcp' => $dhcp->id]);
                $this->fail(new \Exception("DHCP '" . $dhcp->id . "' in failed sync state"));
                return;
            }

            if ($dhcp->sync->status != Sync::STATUS_COMPLETE) {
                Log::warning('DHCP not in sync, retrying in ' . $this->backoff . ' seconds', ['id' => $this->vpc->id, 'dhcp' => $dhcp->id]);
                return $this->release($this->backoff);
            }
        });

        Log::info(get_class($this) . ' : Finished', ['id' => $this->vpc->id]);
    }
}
