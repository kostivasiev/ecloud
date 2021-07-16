<?php

namespace App\Jobs\Nic;

use App\Jobs\Job;
use App\Models\V2\Nic;
use App\Support\Sync;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class AwaitFloatingIpSync extends Job
{
    use Batchable, LoggableModelJob;

    public $tries = 60;
    public $backoff = 10;

    private $model;

    public function __construct(Nic $nic)
    {
        $this->model = $nic;
    }

    public function handle()
    {
        $nic = $this->model;

        if ($nic->floatingIp()->exists() && $nic->floatingIp->sync->status == Sync::STATUS_FAILED) {
            Log::error('Floating IP in failed sync state, abort', ['id' => $this->model->id, 'fip_id' => $nic->floatingIp->id]);
            $this->fail(new \Exception('Floating IP ' . $nic->floatingIp->id .' in failed sync state'));
            return;
        }

        if ($nic->floatingIp()->exists() && $nic->floatingIp->sync->status != Sync::STATUS_COMPLETE) {
            Log::warning('Floating IP not in sync, retrying in ' . $this->backoff . ' seconds', ['id' => $this->model->id, 'fip_id' => $nic->floatingIp->id]);
            return $this->release($this->backoff);
        }
    }
}
