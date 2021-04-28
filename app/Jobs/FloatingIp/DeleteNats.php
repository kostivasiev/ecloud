<?php

namespace App\Jobs\FloatingIp;

use App\Jobs\Job;
use App\Models\V2\FloatingIp;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class DeleteNats extends Job
{
    use Batchable;

    private $floatingIp;

    public function __construct(FloatingIp $floatingIp)
    {
        $this->floatingIp = $floatingIp;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->floatingIp->id]);

        if ($this->floatingIp->sourceNat()->exists()) {
            $this->floatingIp->sourceNat->delete();
        }

        if ($this->floatingIp->destinationNat()->exists()) {
            $this->floatingIp->destinationNat->delete();
        }

        Log::info(get_class($this) . ' : Finished', ['id' => $this->floatingIp->id]);
    }
}
