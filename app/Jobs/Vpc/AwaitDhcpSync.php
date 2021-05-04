<?php

namespace App\Jobs\Vpc;

use App\Jobs\Job;
use App\Models\V2\Sync;
use App\Models\V2\Vpc;
use App\Traits\V2\JobModel;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class AwaitDhcpSync extends Job
{
    use Batchable, JobModel;

    public $tries = 30;
    public $backoff = 5;

    private Vpc $model;

    public function __construct(Vpc $vpc)
    {
        $this->model = $vpc;
    }

    public function handle()
    {
        $this->model->dhcps()->each(function ($dhcp) {
            if ($dhcp->sync->status == Sync::STATUS_FAILED) {
                Log::error('DHCP in failed sync state, abort', ['id' => $this->model->id, 'dhcp' => $dhcp->id]);
                $this->fail(new \Exception("DHCP '" . $dhcp->id . "' in failed sync state"));
                return;
            }

            if ($dhcp->sync->status != Sync::STATUS_COMPLETE) {
                Log::warning('DHCP not in sync, retrying in ' . $this->backoff . ' seconds', ['id' => $this->model->id, 'dhcp' => $dhcp->id]);
                return $this->release($this->backoff);
            }
        });
    }
}
