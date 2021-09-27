<?php

namespace App\Jobs\Nat;

use App\Jobs\Job;
use App\Models\V2\Nat;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class AwaitIPAddressAllocation extends Job
{
    use Batchable, LoggableModelJob;

    public $tries = 30;
    public $backoff = 5;

    private Nat $model;

    public function __construct(Nat $nat)
    {
        $this->model = $nat;
    }

    public function handle()
    {
        if (!empty($this->model->source) && empty($this->model->source->getIPAddress())) {
            Log::warning('Awaiting source NAT resource IP allocation, retrying in ' . $this->backoff . ' seconds', ['id' => $this->model->id, 'source_id' => $this->model->source->id]);
            $this->release($this->backoff);
            return;
        }

        if (!empty($this->model->destination) && empty($this->model->destination->getIPAddress())) {
            Log::warning('Awaiting destination NAT resource IP allocation, retrying in ' . $this->backoff . ' seconds', ['id' => $this->model->id, 'destination_id' => $this->model->destination->id]);
            $this->release($this->backoff);
            return;
        }

        if (!empty($this->model->translated) && empty($this->model->translated->getIPAddress())) {
            Log::warning('Awaiting translated NAT resource IP allocation, retrying in ' . $this->backoff . ' seconds', ['id' => $this->model->id, 'translated_id' => $this->model->translated->id]);
            $this->release($this->backoff);
            return;
        }
    }
}
