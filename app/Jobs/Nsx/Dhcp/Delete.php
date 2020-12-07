<?php

namespace App\Jobs\Nsx\Dhcp;

use App\Jobs\Job;
use App\Models\V2\Dhcp;
use Illuminate\Support\Facades\Log;

class Delete extends Job
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['data' => $this->data]);

        $dhcp = Dhcp::withTrashed()->findOrFail($this->data['id']);
        $dhcp->availabilityZone->nsxService()->delete('/policy/api/v1/infra/dhcp-server-configs/' . $dhcp->id);
        $dhcp->setSyncCompleted();

        Log::info(get_class($this) . ' : Finished', ['data' => $this->data]);
    }
}
