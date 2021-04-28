<?php

namespace App\Jobs\Nat;

use App\Jobs\Job;
use App\Models\V2\Instance;
use App\Models\V2\Nat;
use App\Models\V2\Sync;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class AwaitIPAddressAllocation extends Job
{
    use Batchable;

    public $tries = 30;
    public $backoff = 5;

    private Nat $nat;

    public function __construct(Nat $nat)
    {
        $this->nat = $nat;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->nat->id]);

        if (!empty($this->nat->source) && empty($this->nat->source->ip_address)) {
            Log::warning('Awaiting source NAT resource IP allocation, retrying in ' . $this->backoff . ' seconds', ['id' => $this->nat->id, 'source_id' => $this->nat->source->id]);
            $this->release($this->backoff);
            return;
        }

        if (!empty($this->nat->destination) && empty($this->nat->destination->ip_address)) {
            Log::warning('Awaiting destination NAT resource IP allocation, retrying in ' . $this->backoff . ' seconds', ['id' => $this->nat->id, 'destination_id' => $this->nat->destination->id]);
            $this->release($this->backoff);
            return;
        }

        if (!empty($this->nat->translated) && empty($this->nat->translated->ip_address)) {
            Log::warning('Awaiting translated NAT resource IP allocation, retrying in ' . $this->backoff . ' seconds', ['id' => $this->nat->id, 'translated_id' => $this->nat->translated->id]);
            $this->release($this->backoff);
            return;
        }

        Log::info(get_class($this) . ' : Finished', ['id' => $this->nat->id]);
    }
}
