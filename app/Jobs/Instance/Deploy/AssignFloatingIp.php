<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\FloatingIp\Assign;
use App\Jobs\Job;
use App\Models\V2\FloatingIp;
use App\Models\V2\Instance;
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
        Log::info(get_class($this) . ' : Started', ['data' => $this->data]);

        $instance = Instance::findOrFail($this->data['instance_id']);

        if ((!empty($this->data['floating_ip_id']) || $this->data['requires_floating_ip']) && $instance->nics()->count() < 1) {
            $this->fail(new Exception('AssignFloatingIp failed for ' . $instance->id . ': Failed. Instance has no NIC'));
            return;
        }

        if (!empty($this->data['floating_ip_id'])) {
            $floatingIp = FloatingIp::findOrFail($this->data['floating_ip_id']);
        }

        if ($this->data['requires_floating_ip']) {
            $floatingIp = app()->make(FloatingIp::class);
            $floatingIp->vpc_id = $this->data['vpc_id'];
            $floatingIp->save();
        }

        if (!empty($floatingIp)) {
            $nic = $instance->nics()->first();

            dispatch(new Assign([
                'floating_ip_id' => $floatingIp->id,
                'resource_id' => $nic->id
            ]));

            Log::info('Floating IP (' . $floatingIp->id . ') assigned to NIC (' . $nic->id . ')');
        }

        Log::info(get_class($this) . ' : Finished', ['data' => $this->data]);
    }
}
