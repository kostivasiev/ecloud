<?php

namespace App\Jobs\Vpc;

use App\Jobs\Job;
use App\Models\V2\Vpc;
use App\Support\Sync;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class AwaitDhcpRemoval extends Job
{
    use Batchable, LoggableModelJob;

    public $tries = 30;
    public $backoff = 5;

    private $model;

    public function __construct(Vpc $vpc)
    {
        $this->model = $vpc;
    }

    public function handle()
    {
        if ($this->model->dhcps()->count() > 0) {
            $this->model->dhcps()->each(function ($dhcp) {
                if ($dhcp->sync->status == Sync::STATUS_FAILED) {
                    Log::error('DHCP in failed sync state, abort', ['id' => $this->model->id, 'dhcp' => $dhcp->id]);
                    $this->fail(new \Exception("DHCP '" . $dhcp->id . "' in failed sync state"));
                    return;
                }
            });

            Log::warning($this->model->dhcps()->count() . ' DHCP(s) still attached, retrying in ' . $this->backoff . ' seconds', ['id' => $this->model->id]);
            return $this->release($this->backoff);
        }
    }
}
