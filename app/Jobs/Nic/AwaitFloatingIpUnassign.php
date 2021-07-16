<?php

namespace App\Jobs\Nic;

use App\Jobs\Job;
use App\Models\V2\Nic;
use App\Support\Sync;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class AwaitFloatingIpUnassign extends Job
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

        if ($nic->sourceNat()->exists() && $nic->sourceNat->sync->status == Sync::STATUS_FAILED) {
            Log::error('Source NAT in failed sync state, abort', ['id' => $this->model->id, 'nat_id' => $nic->sourceNat->id]);
            $this->fail(new \Exception("Source NAT '" . $this->model->sourceNat->id . "' in failed sync state"));
            return;
        }

        if ($nic->destinationNat()->exists() && $nic->destinationNat->sync->status == Sync::STATUS_FAILED) {
            Log::error('Destination NAT in failed sync state, abort', ['id' => $this->model->id, 'nat_id' => $nic->destinationNat->id]);
            $this->fail(new \Exception("Destination NAT '" . $this->model->destinationNat->id . "' in failed sync state"));
            return;
        }

        if ($nic->sourceNat()->exists() && $nic->sourceNat->sync->status != Sync::STATUS_COMPLETE) {
            Log::warning('Source NAT not in sync, retrying in ' . $this->backoff . ' seconds', ['id' => $this->model->id, 'nat_id' => $this->model->sourceNat->id]);
            return $this->release($this->backoff);
        }

        if ($nic->destinationNat()->exists() && $nic->destinationNat->sync->status != Sync::STATUS_COMPLETE) {
            Log::warning('Destination NAT not in sync, retrying in ' . $this->backoff . ' seconds', ['id' => $this->model->id, 'nat_id' => $this->model->destinationNat->id]);
            return $this->release($this->backoff);
        }
    }
}
