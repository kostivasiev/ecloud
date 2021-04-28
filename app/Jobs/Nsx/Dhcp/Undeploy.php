<?php

namespace App\Jobs\Nsx\Dhcp;

use App\Jobs\Job;
use App\Models\V2\Dhcp;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class Undeploy extends Job
{
    use Batchable;

    private Dhcp $dhcp;

    public function __construct(Dhcp $dhcp)
    {
        $this->dhcp = $dhcp;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['id' => $this->dhcp->id]);

        $this->dhcp->availabilityZone->nsxService()->delete(
            '/policy/api/v1/infra/dhcp-server-configs/' . $this->dhcp->id
        );

        Log::info(get_class($this) . ' : Finished', ['id' => $this->dhcp->id]);
    }
}
