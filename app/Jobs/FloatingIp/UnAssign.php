<?php

namespace App\Jobs\FloatingIp;

use App\Jobs\Job;
use App\Models\V2\FloatingIp;
use App\Models\V2\Nat;
use Illuminate\Support\Facades\Log;

class UnAssign extends Job
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['data' => $this->data]);

        $floatingIp = FloatingIp::findOrFail($this->data['floating_ip_id']);

        Nat::where('source_id', $floatingIp->getKey())
            ->orWhere('destination_id', $floatingIp->getKey())
            ->orWhere('translated_id', $floatingIp->getKey())
            ->each(function ($nat) {
                $nat->delete();
                Log::info(get_class($this) . ' : NAT ' . $nat->getKey() . ' (' . $nat->action . ') deleted');
            });

        Log::info(get_class($this) . ' : Finished', ['data' => $this->data]);
    }
}
