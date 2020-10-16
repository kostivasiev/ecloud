<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\Job;
use App\Models\V2\FloatingIp;
use App\Models\V2\Instance;
use App\Models\V2\Nat;
use Exception;
use Illuminate\Support\Facades\Log;

class AssignFloatingIp extends Job
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function handle()
    {
        Log::info('Starting AssignFloatingIp for instance ' . $this->data['instance_id']);
        $instance = Instance::findOrFail($this->data['instance_id']);
        $destination = null;

        if ((!empty($this->data['floating_ip_id']) || $this->data['requires_floating_ip'])
            && $instance->nics()->count() < 1) {
            $this->fail(
                new Exception('AssignFloatingIp failed for ' . $instance->id . ': ' . 'Failed. Instance has no NIC')
            );
            return;
        }

        if (!empty($this->data['floating_ip_id'])) {
            $destination = $this->data['floating_ip_id'];
        }

        if ($this->data['requires_floating_ip']) {
            $floatingIp = app()->make(FloatingIp::class);
            $floatingIp->vpc_id = $this->data['vpc_id'];
            $floatingIp->save();
            $destination = $floatingIp->id;
        }

        if (!empty($destination)) {
            $nic = $instance->nics()->first();
            $nat = app()->make(Nat::class);
            $nat->destination = $destination;
            $nat->translated = $nic->id;
            $nat->save();
            Log::info('Floating IP (' . $destination . ') assigned to NIC (' . $nic->id . ')');
        }
    }
}
